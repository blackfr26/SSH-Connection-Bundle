<?php
 
namespace DesarrolloHosting\SshConnectionBundle\Service;
 
use phpseclib\Net\SFTP;
use phpseclib\Crypt\RSA;
 
class SshConnection {
 
    private $user;
    private $private_key_file;
    private $passphrase;
    private $default_ports;
    private $connection_timeout;
    private $exec_timeout;
    private $logger;
    private $last_ip_connected = null;
    private $connection = null;
 
    function __construct($user, $private_key_file, $passphrase, $default_ports, $connection_timeout, $exec_timeout, $logger) {
        $this->user = $user;
        $this->private_key_file = $private_key_file;
        $this->passphrase = $passphrase;
        $this->default_ports = $default_ports;
        $this->connection_timeout = $connection_timeout;
        $this->exec_timeout = $exec_timeout;
        $this->logger = $logger;
    }
 
    public function enableLogging() {
        define('NET_SSH2_LOGGING', 1);
    }
 
    public function disableLogging() {
        define('NET_SSH2_LOGGING', 0);
    }
 
    public function getLog() {
        return $this->connection->getLog();
    }
 
    public function enableQuietMode() {
        $this->connection->enableQuietMode();
    }
 
    public function disableQuietMode() {
        $this->connection->disableQuietMode();
    }
 
    public function getExitStatus() {
        $this->connection->getExitStatus();
    }
 
    public function getConnection() {
        return $this->connection;
    }
 
    public function getLastIpConnected() {
        return $this->last_ip_connected;
    }
 
    public function getExecTimeout() {
        return $this->exec_timeout;
    }
 
    /**
     * 
     * @param type $ip IP adress of the server to connect
     * @param type $port [OPTIONAL] Port to use for the ssh connection. If is not defined then it uses the default values defined in the service configuration.
     * @return bool Connection success
     */
    public function connect($ip, $port = null) {
        $try_ports = isset($port) ? array($port) : $this->default_ports;
 
        $login_success = false;
        foreach ($try_ports as $try_port) {
            try {
                $ssh = new SFTP($ip, $try_port, $this->connection_timeout);
                $key = new RSA();
                $key->setPassword($this->passphrase);
                $key->loadKey(file_get_contents($this->private_key_file));
                if (@$ssh->login($this->user, $key)) {
                    $login_success = true;
                    $this->last_ip_connected = $ip;
                    $this->logger->info("SSH connection success", array("ip" => $ip, "port" => $try_port));
                    break;
                } else {
                    $this->logger->info("SSH connection attempt failed", array("type" => "login", "ip" => $ip, "port" => $try_port));
                }
            } catch (\Exception $ex) {
                $this->logger->info("SSH connection attempt failed", array("type" => "exception", "ip" => $ip, "port" => $try_port, "message" => utf8_encode($ex->getMessage())));
            }
        }
        if (!$login_success) {
            $this->logger->error("SSH connection failed", array("ip" => $ip, "ports" => implode(',', $try_ports)));
            return false;
        }
 
        $this->connection = $ssh;
        $this->enableQuietMode();
        return true;
    }
 
    
    private function testConnection(){
        if (is_null($this->connection)) {
            $this->logger->info("No SSH connection is set");
            return false;
        }
        else if (!$this->connection->isConnected()) {
            $this->logger->info("SSH is disconnected");
            return false;
        }
        else if (!$this->connection->isAuthenticated()) {
            $this->logger->info("SSH connection is not authenticated");
            return false;
        }
        
        return true;
    }
    
    /**
     * 
     * @param string $command For example 'ls -al'.
     * @return array(success,command_output or message)........ (success = true, command_output = Array -> One element per output line.)
     *                                                          (success = false, message = string with the reason)
     *              
     */
    private function exec($command, $new_exec_timeout = false) {
        try {
            $exec_timeout = $new_exec_timeout ? $new_exec_timeout : $this->exec_timeout;
            $this->connection->setTimeout($exec_timeout);
            $result = $this->connection->exec($command);
 
            $this->logger->info("Executing command", array("ip" => $this->last_ip_connected, "command" => $command));
            if ($this->connection->isTimeout()) {
                $this->logger->error("Command execution timeout", array("timeout" => $exec_timeout));
                return array("success" => false, "message" => "Hubo un error durante la ejecución del comando. Se cumplió el timeout de $exec_timeout segundos.");
            }
 
            $result_array = explode("\n", $result);
            if (trim($result_array[sizeof($result_array) - 1]) == '') {
                unset($result_array[sizeof($result_array) - 1]);
            }
 
            $this->logger->info("Command execution success");
            return array("success" => true, "message" => implode("\n", $result_array), "command_output" => $result_array);
        } catch (\Exception $ex) {
            $this->logger->error("Command execution exception", array("message" => $ex->getMessage()));
            return array("success" => false, "message" => "Ocurrió una excepción. " . $ex->getMessage());
        }
    }
    
    /**
     * 
     * @param string/array $commands Commands to execute
     * @param type $new_exec_timeout Override the defualt timeout
     * @return array array("success" => true/false, "message" =>command response/error message, "command_output" => array with each line outputted by succesfull command)
     */
    public function execCommand($commands, $new_exec_timeout = false) {
        if(!$this->testConnection()){
            return array("success" => false, "message" => "No hay ninguna conexión por SSH activa.");
        }
        
        if(!is_array($commands)){
            return $this->exec($commands, $new_exec_timeout);
        }
        
        try{
            $commands_output = array();
            foreach($commands as $index => $command){
                $result = $this->exec($command, $new_exec_timeout);
                if(!$result["success"]){
                    return array("success" => false, "message" => "Error en el comando ".($index + 1)." {$result["message"]}");
                }
                
                $commands_output = array_merge($commands_output, $result["command_output"]);
            }
            
            return array("success" => true, "message" => implode("\n", $commands_output), "command_output" => $commands_output); 
        } catch (\Exception $ex) {
            return array("success" => false, "message" => "Ocurrió una excepción. " . $ex->getMessage());
        }
    }
 
    /**
     * 
     * @param string $remote_file Absolute file location on remote server
     * @param filename/string $local_file If the file doesn't exist it will upload the file $remote_file with $local_file string as content
     * @param bool $append Appends content to remote file
     * @return array array("success" => operation success, "message" => Error message if error)
     */
    public function upload($remote_file, $local_file, $append = false){
        if(!$this->testConnection()){
            return array("success" => false, "message" => "No se pudo ejecutar el comando. No hay ninguna conexión por SSH activa.");
        }
        
        $source = file_exists($local_file) ? SFTP::SOURCE_LOCAL_FILE : SFTP::SOURCE_STRING;
        $mode = $append ? $source | SFTP::RESUME  : $source;
       
        $response =  $this->connection->put($remote_file, $local_file, $mode);
        
        return array("success" => $response);
    }
    
    /**
     * 
     * @param type $remote_file Absolute file location on remote server
     * @param type $local_file If set, path to local file to write to
     * @return array array("success" => operation success, "message" => Error message if error, file contents if success and $local_file is not true)
     */
    public function download($remote_file, $local_file = false){
        if(!$this->testConnection()){
            return array("success" => false, "message" => "No se pudo ejecutar el comando. No hay ninguna conexión por SSH activa.");
        }
        
        $response = $this->connection->get($remote_file, $local_file);
        
        if($local_file){
            return array("success" => $response);
        }
        else if($response){
            return array("success" => true, "message" => $response);
        }
        else{
            $this->logger->error("Couldn't get the file", array("file" => $remote_file));
            return array("success" => false, "message" => "No se pudo obtener el archivo");
        }
    }
}