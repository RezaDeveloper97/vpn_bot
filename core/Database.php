<?php
abstract class Database
{
    use log;
    private $host = '';
    private $db_name = '';
    private $username = '';
    private $password = '';
    private $conn;

    public function __construct()
    {
        $this->host = ConfigContext::get('host');
        $this->db_name = ConfigContext::get('db_name');
        $this->username = ConfigContext::get('username');
        $this->password = ConfigContext::get('password');

        $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8";
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->conn = new \PDO($dsn, $this->username, $this->password, $options);
        } catch (\PDOException $e) {
            $this::log($e->getMessage(), 'dblog.html');
            throw new \Exception("Connection failed: " . $e->getMessage());
        }
    }

    public function query($query, $params = [])
    {
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($query, $params = [])
    {
        $stmt = $this->query($query, $params);
        return $stmt->fetchAll();
    }

    public function fetch($query, $params = [])
    {
        $stmt = $this->query($query, $params);
        return $stmt->fetch();
    }

    public function lastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    public function truncateUserTable()
    {
        try {
            $this->query('TRUNCATE TABLE `users`');
            $this->query('TRUNCATE TABLE `story`');
            $this->query('TRUNCATE TABLE `user_packages`');
        } catch (PDOException $e) {
            $this::log($e->getMessage(), 'dblog.html');
        }
    }
}