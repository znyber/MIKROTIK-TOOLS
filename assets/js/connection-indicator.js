/**
 * Connection Status Indicator Script
 * Menampilkan indikator status koneksi router dengan animasi
 */

// Status koneksi yang mungkin
const CONNECTION_STATUS = {
    CONNECTED: 'connected',
    CONNECTING: 'connecting',
    DISCONNECTED: 'disconnected'
};

// Class untuk mengelola indikator koneksi
class ConnectionIndicator {
    constructor() {
        this.statusContainer = document.getElementById('connectionStatusContainer');
        this.statusText = document.getElementById('connectionStatusText');
        this.routerIcon = document.getElementById('routerIcon');
        this.currentStatus = null;
        this.isDemo = false;
        
        // Periksa apakah dalam mode demo
        if (typeof IS_DEMO_MODE !== 'undefined' && IS_DEMO_MODE) {
            this.isDemo = true;
            this.addDemoBadge();
        }
    }
    
    // Tambahkan badge mode demo jika diperlukan
    addDemoBadge() {
        const demoBadge = document.createElement('span');
        demoBadge.className = 'demo-mode-badge';
        demoBadge.textContent = 'DEMO';
        this.statusContainer.appendChild(demoBadge);
    }
    
    // Update status koneksi
    updateStatus(status) {
        if (this.currentStatus === status) return;
        
        // Hapus class status sebelumnya
        if (this.currentStatus) {
            this.statusContainer.classList.remove(this.currentStatus);
        }
        
        // Set status baru
        this.currentStatus = status;
        this.statusContainer.classList.add(status);
        
        // Update teks status
        switch (status) {
            case CONNECTION_STATUS.CONNECTED:
                this.statusText.textContent = 'Connected';
                this.routerIcon.classList.add('active');
                break;
            case CONNECTION_STATUS.CONNECTING:
                this.statusText.textContent = 'Connecting...';
                this.routerIcon.classList.add('active');
                break;
            case CONNECTION_STATUS.DISCONNECTED:
                this.statusText.textContent = 'Disconnected';
                this.routerIcon.classList.remove('active');
                break;
            default:
                this.statusText.textContent = 'Unknown';
        }
    }
    
    // Periksa status koneksi secara berkala
    startStatusCheck(checkInterval = 10000) {
        // Set status awal
        this.updateInitialStatus();
        
        // Periksa status koneksi secara berkala
        setInterval(() => {
            if (this.isDemo) {
                // Dalam mode demo, selalu terhubung
                this.updateStatus(CONNECTION_STATUS.CONNECTED);
                return;
            }
            
            this.checkConnectionStatus();
        }, checkInterval);
    }
    
    // Set status awal berdasarkan status global
    updateInitialStatus() {
        if (this.isDemo) {
            this.updateStatus(CONNECTION_STATUS.CONNECTED);
            return;
        }
        
        // Periksa variabel global router_connected
        if (typeof ROUTER_CONNECTED !== 'undefined') {
            const status = ROUTER_CONNECTED === true ? 
                CONNECTION_STATUS.CONNECTED : 
                CONNECTION_STATUS.DISCONNECTED;
            this.updateStatus(status);
        } else {
            this.checkConnectionStatus();
        }
    }
    
    // Periksa status koneksi router secara real-time
    checkConnectionStatus() {
        fetch('/api/connection_status.php')
            .then(response => response.json())
            .then(data => {
                const status = data.connected ? 
                    CONNECTION_STATUS.CONNECTED : 
                    CONNECTION_STATUS.DISCONNECTED;
                this.updateStatus(status);
            })
            .catch(error => {
                console.error('Error checking connection status:', error);
                this.updateStatus(CONNECTION_STATUS.DISCONNECTED);
            });
    }
}

// Inisialisasi indikator setelah DOM dimuat
document.addEventListener('DOMContentLoaded', () => {
    const connectionIndicator = new ConnectionIndicator();
    connectionIndicator.startStatusCheck();
});