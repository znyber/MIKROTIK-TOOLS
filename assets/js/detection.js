/**
 * H4N5VS Mikrotik System Security
 * Threat detection and analysis module
 */

// Constants for detection thresholds 
const THRESHOLDS = {
    // UDP Flood thresholds
    UDP_FLOOD: {
        CONNECTIONS_PER_IP: 100,     // Number of UDP connections from a single IP
        PACKETS_PER_SECOND: 500,     // Number of UDP packets per second
        DESTINATION_PORT_SPREAD: 20  // Number of different destination ports
    },
    
    // TCP SYN Flood thresholds
    TCP_SYN_FLOOD: {
        SYN_CONNECTIONS: 50,       // Number of half-open TCP connections
        SYN_RATIO: 0.8,            // Ratio of SYN packets to total TCP packets
        CONNECTION_TIMEOUTS: 30    // Number of connection timeouts
    },
    
    // DNS Flood thresholds
    DNS_FLOOD: {
        DNS_QUERIES_PER_SECOND: 100,  // Number of DNS queries per second
        UNIQUE_DOMAINS: 50,           // Number of unique domains queried
        QUERY_FAILURES: 30            // Number of DNS query failures
    },
    
    // DDoS thresholds
    DDOS: {
        TOTAL_CONNECTIONS: 5000,      // Total number of connections
        TRAFFIC_MBPS: 100,            // Traffic in Mbps
        IP_SOURCE_COUNT: 20           // Number of different source IPs
    },
    
    // Brute force thresholds
    BRUTE_FORCE: {
        LOGIN_FAILURES: 5,            // Number of failed login attempts
        LOGIN_ATTEMPTS_PERIOD: 300,   // Period for login attempts (seconds)
        UNIQUE_CREDENTIALS: 3         // Number of unique credentials tried
    },
    
    // Botnet activity thresholds
    BOTNET: {
        KNOWN_C2_CONNECTIONS: 1,      // Connections to known C2 servers
        UNUSUAL_PORT_ACTIVITY: 5,     // Connections to unusual ports
        PERIODIC_CONNECTIONS: 10      // Regular, periodic connection attempts
    },
    
    // Malware thresholds
    MALWARE: {
        SUSPICIOUS_SCRIPTS: 2,        // Number of suspicious scripts running
        MODIFIED_SYSTEM_FILES: 1,     // Number of modified system files
        UNEXPECTED_PROCESSES: 3       // Number of unexpected processes
    }
};

/**
 * Analyzes connections for UDP flood attack patterns
 * @param {Array} connections - List of active connections
 * @returns {Object} Detection result with threat details if found
 */
function detectUDPFlood(connections) {
    const result = { detected: false, details: {} };
    
    // Group UDP connections by source IP
    const ipConnections = {};
    let udpConnections = connections.filter(conn => conn.protocol === 'udp');
    
    udpConnections.forEach(conn => {
        if (!ipConnections[conn.src_address]) {
            ipConnections[conn.src_address] = [];
        }
        ipConnections[conn.src_address].push(conn);
    });
    
    // Check for IPs with excessive UDP connections
    for (const ip in ipConnections) {
        const ipConns = ipConnections[ip];
        
        // Count connections and unique destination ports
        const destPorts = new Set(ipConns.map(conn => conn.dst_port));
        
        // If thresholds exceeded, mark as attack
        if (ipConns.length > THRESHOLDS.UDP_FLOOD.CONNECTIONS_PER_IP && 
            destPorts.size > THRESHOLDS.UDP_FLOOD.DESTINATION_PORT_SPREAD) {
            
            result.detected = true;
            result.details = {
                type: 'udp_flood',
                source_ip: ip,
                connections: ipConns.length,
                destination_ports: destPorts.size,
                target: ipConns[0].dst_address, // Most common target
                severity: 'high'
            };
            
            // Return after first detection to avoid multiple alerts
            return result;
        }
    }
    
    return result;
}

/**
 * Analyzes connections for TCP SYN flood attack patterns
 * @param {Array} connections - List of active connections
 * @param {Object} connectionStats - Statistics about connections
 * @returns {Object} Detection result with threat details if found
 */
function detectTCPSYNFlood(connections, connectionStats) {
    const result = { detected: false, details: {} };
    
    // Get all TCP connections in SYN state
    const synConnections = connections.filter(conn => 
        conn.protocol === 'tcp' && conn.tcp_state === 'syn-sent');
    
    // Group SYN connections by source IP
    const ipSynConnections = {};
    synConnections.forEach(conn => {
        if (!ipSynConnections[conn.src_address]) {
            ipSynConnections[conn.src_address] = [];
        }
        ipSynConnections[conn.src_address].push(conn);
    });
    
    // Check for IPs with excessive SYN connections
    for (const ip in ipSynConnections) {
        const ipConns = ipSynConnections[ip];
        
        // If threshold exceeded, mark as attack
        if (ipConns.length > THRESHOLDS.TCP_SYN_FLOOD.SYN_CONNECTIONS) {
            // Find the most common target
            const targets = {};
            ipConns.forEach(conn => {
                if (!targets[conn.dst_address]) {
                    targets[conn.dst_address] = 0;
                }
                targets[conn.dst_address]++;
            });
            
            const mainTarget = Object.keys(targets).reduce((a, b) => 
                targets[a] > targets[b] ? a : b, Object.keys(targets)[0]);
            
            result.detected = true;
            result.details = {
                type: 'tcp_syn_flood',
                source_ip: ip,
                connections: ipConns.length,
                target: mainTarget,
                severity: 'high'
            };
            
            // Return after first detection to avoid multiple alerts
            return result;
        }
    }
    
    return result;
}

/**
 * Analyzes connections for DNS flood attack patterns
 * @param {Array} connections - List of active connections
 * @param {Object} dnsStats - DNS query statistics
 * @returns {Object} Detection result with threat details if found
 */
function detectDNSFlood(connections, dnsStats) {
    const result = { detected: false, details: {} };
    
    // Skip if no DNS stats available
    if (!dnsStats || !dnsStats.queries_per_second) {
        return result;
    }
    
    // Check if DNS queries per second exceed threshold
    if (dnsStats.queries_per_second > THRESHOLDS.DNS_FLOOD.DNS_QUERIES_PER_SECOND) {
        // Get all UDP connections to port 53 (DNS)
        const dnsConnections = connections.filter(conn => 
            (conn.protocol === 'udp' || conn.protocol === 'tcp') && 
            conn.dst_port === 53);
        
        // Group DNS connections by source IP
        const ipDnsConnections = {};
        dnsConnections.forEach(conn => {
            if (!ipDnsConnections[conn.src_address]) {
                ipDnsConnections[conn.src_address] = [];
            }
            ipDnsConnections[conn.src_address].push(conn);
        });
        
        // Find IP with most DNS connections
        let maxConnections = 0;
        let attackerIp = '';
        
        for (const ip in ipDnsConnections) {
            if (ipDnsConnections[ip].length > maxConnections) {
                maxConnections = ipDnsConnections[ip].length;
                attackerIp = ip;
            }
        }
        
        // If we found a potential attacker
        if (maxConnections > 0) {
            result.detected = true;
            result.details = {
                type: 'dns_flood',
                source_ip: attackerIp,
                connections: maxConnections,
                queries_per_second: dnsStats.queries_per_second,
                target: 'DNS Servers',
                severity: 'high'
            };
        }
    }
    
    return result;
}

/**
 * Analyzes overall traffic patterns for DDoS attack signatures
 * @param {Array} connections - List of active connections
 * @param {Object} trafficStats - Traffic statistics
 * @returns {Object} Detection result with threat details if found
 */
function detectDDoS(connections, trafficStats) {
    const result = { detected: false, details: {} };
    
    // Skip if no traffic stats available
    if (!trafficStats || !trafficStats.total_mbps) {
        return result;
    }
    
    // Get unique source IPs
    const sourceIps = new Set(connections.map(conn => conn.src_address));
    
    // Check if traffic and connection thresholds are exceeded
    if (connections.length > THRESHOLDS.DDOS.TOTAL_CONNECTIONS && 
        trafficStats.total_mbps > THRESHOLDS.DDOS.TRAFFIC_MBPS && 
        sourceIps.size > THRESHOLDS.DDOS.IP_SOURCE_COUNT) {
        
        // Find the most common target
        const targets = {};
        connections.forEach(conn => {
            if (!targets[conn.dst_address]) {
                targets[conn.dst_address] = 0;
            }
            targets[conn.dst_address]++;
        });
        
        const mainTarget = Object.keys(targets).reduce((a, b) => 
            targets[a] > targets[b] ? a : b, Object.keys(targets)[0]);
        
        result.detected = true;
        result.details = {
            type: 'ddos',
            source_ips: sourceIps.size,
            connections: connections.length,
            traffic_mbps: trafficStats.total_mbps,
            target: mainTarget,
            severity: 'critical'
        };
    }
    
    return result;
}

/**
 * Analyzes login attempts for brute force attack patterns
 * @param {Array} loginLogs - List of login attempt logs
 * @returns {Object} Detection result with threat details if found
 */
function detectBruteForce(loginLogs) {
    const result = { detected: false, details: {} };
    
    // Skip if no login logs available
    if (!loginLogs || loginLogs.length === 0) {
        return result;
    }
    
    // Group login attempts by source IP
    const ipLoginAttempts = {};
    loginLogs.forEach(log => {
        if (!ipLoginAttempts[log.src_address]) {
            ipLoginAttempts[log.src_address] = [];
        }
        ipLoginAttempts[log.src_address].push(log);
    });
    
    // Check each source IP for brute force patterns
    for (const ip in ipLoginAttempts) {
        const attempts = ipLoginAttempts[ip];
        
        // Count failed login attempts
        const failedAttempts = attempts.filter(log => log.success === false);
        
        // Count unique credentials tried
        const uniqueCredentials = new Set(attempts.map(log => log.username));
        
        // Check if thresholds are exceeded
        if (failedAttempts.length >= THRESHOLDS.BRUTE_FORCE.LOGIN_FAILURES && 
            uniqueCredentials.size >= THRESHOLDS.BRUTE_FORCE.UNIQUE_CREDENTIALS) {
            
            // Determine the service being targeted (ssh, winbox, web, etc.)
            const services = {};
            attempts.forEach(log => {
                if (!services[log.service]) {
                    services[log.service] = 0;
                }
                services[log.service]++;
            });
            
            const mainService = Object.keys(services).reduce((a, b) => 
                services[a] > services[b] ? a : b, Object.keys(services)[0]);
            
            result.detected = true;
            result.details = {
                type: 'brute_force',
                source_ip: ip,
                failed_attempts: failedAttempts.length,
                unique_credentials: uniqueCredentials.size,
                target_service: mainService,
                severity: 'medium'
            };
            
            // Return after first detection to avoid multiple alerts
            return result;
        }
    }
    
    return result;
}

/**
 * Analyzes connection patterns for botnet activity
 * @param {Array} connections - List of active connections
 * @param {Array} knownC2Servers - List of known Command & Control servers
 * @returns {Object} Detection result with threat details if found
 */
function detectBotnet(connections, knownC2Servers) {
    const result = { detected: false, details: {} };
    
    // Skip if no known C2 servers list available
    if (!knownC2Servers || knownC2Servers.length === 0) {
        return result;
    }
    
    // Check for connections to known C2 servers
    for (const connection of connections) {
        if (knownC2Servers.includes(connection.dst_address)) {
            result.detected = true;
            result.details = {
                type: 'botnet',
                source_ip: connection.src_address,
                c2_server: connection.dst_address,
                port: connection.dst_port,
                protocol: connection.protocol,
                severity: 'critical'
            };
            
            // Return after first detection to avoid multiple alerts
            return result;
        }
    }
    
    // Check for unusual port activity patterns
    // (This is a simplified example - real botnet detection would be more complex)
    const suspiciousPorts = [6667, 1080, 8080, 9050, 16464, 10000];
    
    for (const connection of connections) {
        if (suspiciousPorts.includes(connection.dst_port)) {
            // Count connections to this suspicious port
            const similarConnections = connections.filter(conn => 
                conn.dst_port === connection.dst_port && 
                conn.src_address !== connection.src_address);
            
            if (similarConnections.length >= THRESHOLDS.BOTNET.UNUSUAL_PORT_ACTIVITY) {
                result.detected = true;
                result.details = {
                    type: 'botnet',
                    source_ip: connection.src_address,
                    suspicious_port: connection.dst_port,
                    similar_connections: similarConnections.length,
                    target: connection.dst_address,
                    severity: 'high'
                };
                
                // Return after first detection to avoid multiple alerts
                return result;
            }
        }
    }
    
    return result;
}

/**
 * Analyzes system and process activity for malware indicators
 * @param {Object} systemStatus - System status information
 * @param {Array} processes - List of running processes
 * @returns {Object} Detection result with threat details if found
 */
function detectMalware(systemStatus, processes) {
    const result = { detected: false, details: {} };
    
    // Skip if no system status or processes available
    if (!systemStatus || !processes) {
        return result;
    }
    
    // Check for known malicious process names
    const suspiciousProcessNames = [
        'rustobot', 'mipsbot', 'armbot', 'meterpreter', 
        'xmrminer', 'cryptominer', 'coinminer'
    ];
    
    const suspiciousProcesses = processes.filter(process => 
        suspiciousProcessNames.some(name => process.name.toLowerCase().includes(name)));
    
    if (suspiciousProcesses.length > 0) {
        result.detected = true;
        result.details = {
            type: 'malware',
            process_name: suspiciousProcesses[0].name,
            pid: suspiciousProcesses[0].pid,
            user: suspiciousProcesses[0].user,
            severity: 'critical'
        };
        
        return result;
    }
    
    // Check for unexpected system behavior
    if (systemStatus.cpu_load > 90 && processes.length > systemStatus.normal_process_count * 1.5) {
        // High CPU with many processes might indicate cryptomining
        result.detected = true;
        result.details = {
            type: 'malware',
            indicator: 'high_resource_usage',
            cpu_load: systemStatus.cpu_load,
            process_count: processes.length,
            normal_count: systemStatus.normal_process_count,
            severity: 'medium'
        };
    }
    
    return result;
}

/**
 * Master function to detect all types of threats
 * @param {Object} data - All data needed for threat detection
 * @returns {Array} List of detected threats
 */
function detectThreats(data) {
    const threats = [];
    
    // Check for UDP flood attacks
    const udpFloodResult = detectUDPFlood(data.connections);
    if (udpFloodResult.detected) {
        threats.push(udpFloodResult.details);
    }
    
    // Check for TCP SYN flood attacks
    const tcpSynFloodResult = detectTCPSYNFlood(data.connections, data.connectionStats);
    if (tcpSynFloodResult.detected) {
        threats.push(tcpSynFloodResult.details);
    }
    
    // Check for DNS flood attacks
    const dnsFloodResult = detectDNSFlood(data.connections, data.dnsStats);
    if (dnsFloodResult.detected) {
        threats.push(dnsFloodResult.details);
    }
    
    // Check for DDoS attacks
    const ddosResult = detectDDoS(data.connections, data.trafficStats);
    if (ddosResult.detected) {
        threats.push(ddosResult.details);
    }
    
    // Check for brute force attacks
    const bruteForceResult = detectBruteForce(data.loginLogs);
    if (bruteForceResult.detected) {
        threats.push(bruteForceResult.details);
    }
    
    // Check for botnet activity
    const botnetResult = detectBotnet(data.connections, data.knownC2Servers);
    if (botnetResult.detected) {
        threats.push(botnetResult.details);
    }
    
    // Check for malware
    const malwareResult = detectMalware(data.systemStatus, data.processes);
    if (malwareResult.detected) {
        threats.push(malwareResult.details);
    }
    
    return threats;
}
