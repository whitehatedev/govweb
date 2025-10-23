<?php
class DistrictModel {
    private $conn;
    private $table_name = "districts";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getDistrictsByState($state_id) {
        $query = "SELECT district_id, district_name, district_name_hindi
                  FROM " . $this->table_name . "
                  WHERE state_id = :state_id
                  ORDER BY district_name";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":state_id", $state_id);
        $stmt->execute();

        return $stmt;
    }

    public function getDistrictPerformance($district_id, $fy_id = null) {
        if (!$fy_id) {
            $fy_id = $this->getCurrentFinancialYear();
        }

        $query = "SELECT * FROM district_performance
                  WHERE district_id = :district_id
                  AND fy_id = :fy_id
                  ORDER BY month_year DESC
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":district_id", $district_id);
        $stmt->bindParam(":fy_id", $fy_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getCurrentFinancialYear() {
        // Logic to determine current financial year
        $current_month = date('n');
        $current_year = date('Y');

        if ($current_month >= 4) { // April to March
            return 'FY' . $current_year . '-' . substr($current_year + 1, 2);
        } else {
            return 'FY' . ($current_year - 1) . '-' . substr($current_year, 2);
        }
    }
}
?>