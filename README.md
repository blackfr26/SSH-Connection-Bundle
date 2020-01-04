SshConnectionBundle
This bundle allows you to connect to multiple servers that share the authentication credentials.

Configuration
You must configure the bundle adding this parameters in your parameters.yml file:

ssh_connection_user: 'ssh_user' #for example 'root' ssh_connection_private_key_file: 'path_to_private_hey_file' ssh_connection_passphrase: 'my_passphrase' ssh_connection_default_ports: #array with the ssh ports to try to connect. It will try in the same order as defined here (i.e. in this case it will try to connect using port 2420. If it fails, it will try with port 22 ) - 2420 - 22 ssh_connection_connection_timeout: 5 #seconds to try connecting to a server ssh_connection_exec_timeout: 20 #second to wait for a command to finish its execution

Services
It provides one services that is automatically loaded, there is no need to add it to services.yml

ssh_connection: With following main methods:

connect: that sets the internal connection using the credentials stored in the parameters.yml.
execCommand: that executes the bash command given as parameter (or an array of commands).
upload: uploads a file os string from the server.
download: downloads a file from the server.