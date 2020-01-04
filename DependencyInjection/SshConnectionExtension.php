<?php
 
namespace DesarrolloHosting\SshConnectionBundle\DependencyInjection;
 
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
 
/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class SshConnectionExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        
        $container->setParameter('ssh_connection.user', $config['user']);
        $container->setParameter('ssh_connection.private_key_file', $config['private_key_file']);
        $container->setParameter('ssh_connection.passphrase', $config['passphrase']);
        $container->setParameter('ssh_connection.default_ports', $config['default_ports']);
        $container->setParameter('ssh_connection.connection_timeout', $config['connection_timeout']);
        $container->setParameter('ssh_connection.exec_timeout', $config['exec_timeout']);
 
        $loader = new Loader\YamlFileLoader($container, new FileLocator(_DIR_.'/../Resources/config'));
        $loader->load('services.yml');
    }
    
     public function getAlias() {
        return 'ssh_connection';
    }
}