<?php
namespace Grav\Plugin;
use \Grav\Common\Plugin;
class VertretungPlugin extends Plugin
{
    public static function getSubscribedEvents()
    {
        return [
            'onTwigExtensions' => ['onTwigExtensions', 0]
        ];
    }
    public function onTwigExtensions()
    {
        require_once(__DIR__ . '/twig/VertretungTwigExtension.php');
        $this->grav['twig']->twig->addExtension(new VertretungTwigExtension());
    }
}