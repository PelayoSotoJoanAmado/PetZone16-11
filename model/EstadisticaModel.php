<?php
/**
 * Modelo de Estadísticas - PetZone
 */

require_once __DIR__ . '/../config/conexion.php';

class EstadisticaModel {
    private $db;
    
    public function __construct() {
        try {
            $this->db = getDB();
        } catch (Exception $e) {
            error_log("Error al conectar con la base de datos: " . $e->getMessage());
            throw new Exception("Error de conexión con la base de datos");
        }
    }
    
    public function obtenerEstadisticasDashboard() {
        try {
            // Verificar si la vista existe
            $stmt = $this->db->query("SHOW TABLES LIKE 'estadisticas_dashboard'");
            $vistaExiste = $stmt->fetch();
            
            if ($vistaExiste) {
                $stmt = $this->db->query("SELECT * FROM estadisticas_dashboard");
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                // Si la vista no existe, calcular las estadísticas manualmente
                return $this->calcularEstadisticasDashboard();
            }
            
        } catch (PDOException $e) {
            error_log("Error en obtenerEstadisticasDashboard: " . $e->getMessage());
            throw new Exception("Error al obtener estadísticas del dashboard");
        }
    }
    
    
    private function calcularEstadisticasDashboard() {
        try {
            $estadisticas = [];
            
            // Productos
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM productos WHERE activo = 1");
            $estadisticas['total_productos'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM productos WHERE stock < 10 AND activo = 1");
            $estadisticas['productos_stock_bajo'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Sliders
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM sliders WHERE activo = 1");
            $estadisticas['total_sliders_activos'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Anuncios
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM anuncios WHERE activo = 1");
            $estadisticas['total_anuncios_activos'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Pedidos
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM pedidos WHERE DATE(fecha_creacion) = CURDATE()");
            $estadisticas['pedidos_hoy'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM pedidos WHERE estado = 'pendiente'");
            $estadisticas['pedidos_pendientes'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Ventas
            $stmt = $this->db->query("
                SELECT COALESCE(SUM(total), 0) as total 
                FROM pedidos 
                WHERE estado != 'cancelado'
            ");
            $estadisticas['ventas_totales'] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            $stmt = $this->db->query("
                SELECT COALESCE(SUM(total), 0) as total 
                FROM pedidos 
                WHERE MONTH(fecha_creacion) = MONTH(CURDATE()) 
                AND YEAR(fecha_creacion) = YEAR(CURDATE())
                AND estado != 'cancelado'
            ");
            $estadisticas['ventas_mes_actual'] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            return $estadisticas;
            
        } catch (PDOException $e) {
            error_log("Error en calcularEstadisticasDashboard: " . $e->getMessage());
            throw new Exception("Error al calcular estadísticas del dashboard");
        }
    }

}