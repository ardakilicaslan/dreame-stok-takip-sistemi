<?php

class IndexController {

    private $conn;

    public function __construct($db)

    {

        $this->conn = $db;

    }

    public function index() {

        try {

            

            $total_models = $this->conn->query("SELECT COUNT(*) FROM models")->fetchColumn();

            $total_customers = $this->conn->query("SELECT COUNT(*) FROM customers")->fetchColumn();

            $total_products = $this->conn->query("SELECT COUNT(*) FROM serial_numbers WHERE sold = 0")->fetchColumn();

            

            $stmt = $this->conn->query("

                SELECT 

                    m.barcode, 

                    m.name, 

                    b.name AS brand_name, 

                    c.name AS category_name,

                    (SELECT COUNT(*) FROM serial_numbers sn WHERE sn.barcode = m.barcode AND sn.sold = 0) AS total_quantity

                FROM models m

                LEFT JOIN brands b ON m.brand_id = b.id

                LEFT JOIN categories c ON m.category_id = c.id

                GROUP BY m.barcode

            ");

            $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->render('index', [

                'title' => 'Genel Bakış',

                'total_models' => $total_models,

                'total_customers' => $total_customers,

                'total_products' => $total_products,

                'models' => $models

            ]);

        } catch (PDOException $e) {

            trigger_error('Database error: ' . $e->getMessage(), E_USER_ERROR);

        }

    }

    private function render($view, $data = []) {

        global $conn;

        require_once __DIR__ . '/../error_handler.php';

        require_once __DIR__ . '/../security_functions.php';

        extract($data);

        

        $viewPath = __DIR__ . "/../views/{$view}.php";

        if (file_exists($viewPath)) {

            include $viewPath;

        } else {

            trigger_error("View not found: {$viewPath}", E_USER_ERROR);

        }

    }

}

