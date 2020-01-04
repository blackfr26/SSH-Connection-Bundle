<?php
 
namespace DesarrolloHosting\SshConnectionBundle;
 
use Symfony\Component\HttpKernel\Bundle\Bundle;
use DesarrolloHosting\SshConnectionBundle\DependencyInjection\SshConnectionExtension;
 
class DesarrolloHostingSshConnectionBundle extends Bundle
{
    public function getContainerExtension() {
        return new SshConnectionExtension();
    }
}