<?php
/**
 * Controlador de Carrito - PetZone
 */

require_once __DIR__ . '/../model/CarritoModel.php';

class CarritoController {
    private $model;
    
    public function __construct() {
        $this->model = new CarritoModel();
    }
    
    public function index() {
        $this->get();
    }
    
    public function add() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $sessionId = $this->getCartSessionId();
            
            $productoId = (int)($data['producto_id'] ?? 0);
            $cantidad = (int)($data['cantidad'] ?? 1);
            
            if ($productoId <= 0 || $cantidad <= 0) {
                $this->jsonResponse(['success' => false, 'message' => 'Datos inválidos'], 400);
                return;
            }
            
            $result = $this->model->agregarAlCarrito($sessionId, $productoId, $cantidad);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Producto agregado al carrito',
                'cart' => $result
            ]);
            
        } catch (Exception $e) {
            error_log("CarritoController - add Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function update() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $sessionId = $this->getCartSessionId();
            
            $productoId = (int)($data['producto_id'] ?? 0);
            $cantidad = (int)($data['cantidad'] ?? 1);
            
            if ($productoId <= 0 || $cantidad < 0) {
                $this->jsonResponse(['success' => false, 'message' => 'Datos inválidos'], 400);
                return;
            }
            
            $result = $this->model->actualizarCarrito($sessionId, $productoId, $cantidad);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Carrito actualizado',
                'cart' => $result
            ]);
            
        } catch (Exception $e) {
            error_log("CarritoController - update Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function remove() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $sessionId = $this->getCartSessionId();
            
            $productoId = (int)($data['producto_id'] ?? 0);
            
            if ($productoId <= 0) {
                $this->jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
                return;
            }
            
            $result = $this->model->eliminarDelCarrito($sessionId, $productoId);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Producto eliminado',
                'cart' => $result
            ]);
            
        } catch (Exception $e) {
            error_log("CarritoController - remove Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function get() {
        try {
            $sessionId = $this->getCartSessionId();
            $carrito = $this->model->obtenerCarrito($sessionId);
            
            $this->jsonResponse([
                'success' => true,
                'items' => $carrito['items'],
                'totales' => $carrito['totales']
            ]);
            
        } catch (Exception $e) {
            error_log("CarritoController - get Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function clear() {
        try {
            $sessionId = $this->getCartSessionId();
            $this->model->vaciarCarrito($sessionId);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Carrito vaciado',
                'cart' => ['count' => 0, 'subtotal' => 0, 'total' => 0]
            ]);
            
        } catch (Exception $e) {
            error_log("CarritoController - clear Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function checkout() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $sessionId = $this->getCartSessionId();
            
            $nombre = $this->sanitize($data['nombre'] ?? '');
            $email = $this->sanitize($data['email'] ?? '');
            $telefono = $this->sanitize($data['telefono'] ?? '');
            $direccion = $this->sanitize($data['direccion'] ?? '');
            $metodo_pago = $this->sanitize($data['metodo_pago'] ?? '');
            $notas = $this->sanitize($data['notas'] ?? '');
            
            if (empty($nombre) || empty($email) || empty($telefono) || empty($direccion) || empty($metodo_pago)) {
                $this->jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
                return;
            }
            
            $result = $this->model->procesarCheckout($sessionId, [
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'metodo_pago' => $metodo_pago,
                'notas' => $notas
            ]);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'codigo_pedido' => $result
            ]);
            
        } catch (Exception $e) {
            error_log("CarritoController - checkout Error: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    private function getCartSessionId() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['cart_session_id'])) {
            $_SESSION['cart_session_id'] = uniqid('cart_', true);
        }
        
        return $_SESSION['cart_session_id'];
    }
    
    private function sanitize($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}