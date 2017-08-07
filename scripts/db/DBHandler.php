<?php

/**
 * Class to handle all db operations
 */
class DbHandler
{
    private $conn;

    public function __construct()
    {
        require_once dirname(__FILE__) . '/DBConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    public function _cleanUp($var)
    {
        return mysqli_real_escape_string($this->conn, $var);
    }

    public function start_transaction()
    {
        if (is_object($this->conn)) {
            return $this->conn->autocommit(false);
        }
    }

    public function commit()
    {
        if (is_object($this->conn)) {
            if ($this->conn->commit()) {
                return $this->conn->autocommit(true);
            } else {
                $this->conn->autocommit(true);
                throw new Exception;
            }
        }
    }

    public function rollback()
    {
        if (is_object($this->conn)) {
            if ($this->conn->rollback()) {
                return $this->conn->autocommit(true);
            } else {
                $this->conn->autocommit(true);
                throw new Exception;
            }
        }
    }

    public function insertGasStation($idTankerkoenig, $diesel, $e5, $e10)
    {
        $idTankerkoenig = $this->_cleanUp($idTankerkoenig);
        $diesel = $this->_cleanUp($diesel);
        $e5 = $this->_cleanUp($e5);
        $e10 = $this->_cleanUp($e10);

        $stmt = $this->conn->prepare("INSERT INTO `db692610761`.`Tankstellen` (`idTankerkoenig`, `priceDiesel`, `priceE5`, `priceE10`) VALUES(?, ?, ?, ?)");

        if ($stmt) {
            $stmt->bind_param("sddd", $idTankerkoenig, $diesel, $e5, $e10);
            $result = $stmt->execute();
            $stmt->close();

      // Check for successful insertion
      if ($result) {
          // Gas station successfully inserted
        return true;
      } else {
          // Failed to add gas station
        return false;
      }
        } else {
            var_dump($stmt);
            exit;
        }

        return false;
    }

    public function getPricesForStation($station_id)
    {
        $stationId = $this->_cleanUp($station_id);

        $stmt = $this->conn->prepare("SELECT t.id, t.idTankerkoenig, t.priceDiesel, t.priceE5, t.priceE10, t.timestamp FROM Tankstellen t WHERE t.idTankerkoenig = ?");

        if ($stmt) {
            $stmt->bind_param("s", $stationId);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($id, $idTankerkoenig, $priceDiesel, $priceE5, $priceE10, $timestamp);

            $response = array();

            while ($stmt->fetch()) {
                array_push($response, [
                'id'   => $id,
                'idTankerkoenig' => $idTankerkoenig,
                'priceDiesel' => $priceDiesel,
                'priceE5' => $priceE5,
                'priceE10' => $priceE10,
                'timestamp' => $timestamp
            ]);
            }

            $stmt->close();
        } else {
            var_dump($stmt);
            exit;
        }

        return json_encode($response);
    }
}
