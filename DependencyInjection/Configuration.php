<?php
 
namespace DesarrolloHosting\SshConnectionBundle\DependencyInjection;
 
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
 
/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('ssh_connection');
 
        $rootNode
            ->children()
                ->scalarNode('user')->isRequired()->end()
                ->scalarNode('private_key_file')->isRequired()->end()
                ->scalarNode('passphrase')->isRequired()->end()
                ->arrayNode('default_ports')->defaultValue(array(22))
                    ->prototype('integer')->end()
                ->end() //default_ports
                ->integerNode('connection_timeout')->defaultValue(10)->end()
                ->integerNode('exec_timeout')->defaultValue(20)->end()
            ->end()
        ;
 
        return $treeBuilder;
    }
}