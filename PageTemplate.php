<?php
/**
 * Definition of class PageTemplate
 *
 * @copyright  2014-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 * @package    justso\justgen
 */

namespace justso\justgen;

use justso\justapi\SystemEnvironmentInterface;

require dirname(dirname(__DIR__)) . '/autoload.php';

/**
 * Class PageTemplate
 * @package justso\justgen
 */
class PageTemplate
{
    /**
     * @var string
     */
    private $template;

    /**
     * @var string[]
     */
    private $languages;

    /**
     * @var array
     */
    private $params;

    /**
     * @param string $template
     * @param string[] $languages
     * @param array $params
     */
    public function __construct($template, $languages, $params = [])
    {
        $this->template  = $template;
        $this->languages = $languages;
        $this->params    = $params;
    }

    /**
     * Generate a page in the specified language.
     *
     * @param string $language
     * @param string $page
     * @param SystemEnvironmentInterface $env
     * @return string
     */
    public function generate($language, $page, SystemEnvironmentInterface $env)
    {
        $fs = $env->getFileSystem();
        $appRoot = $env->getBootstrap()->getAppRoot();
        $smarty = $this->setupSmarty($language, $page, $env);
        /** @var \justso\justtexts\TextInterface $pageTexts */
        $pageTexts = $env->getDIC()->get('\justso\justtexts\Text', [$env, $page]);
        $smarty->assign(array_map(
            function ($info) {
                return $info['content'];
            },
            $pageTexts->getPageTexts($language)
        ));

        $previous = set_error_handler(function () {
            return true;
        });
        $content = $smarty->fetch($this->template . '.tpl');
        set_error_handler($previous);

        $processorFile = $appRoot . '/processors/' . $this->template . '.php';
        if ($fs->fileExists($processorFile)) {
            require_once($fs->getRealPath($processorFile));
            $processor = new $this->template($env->getBootstrap()->getWebAppUrl());
            if ($processor instanceof ProcessorInterface) {
                $content = $processor->process($content, $page);
            }
        }

        return $content;
    }

    /**
     * Finds variables in template
     *
     * @param SystemEnvironmentInterface $env  System environment
     * @param string                     $page Name of page
     * @return string[]                        Names of variables in template
     */
    public function getSmartyVars(SystemEnvironmentInterface $env, $page)
    {
        $smarty = $this->setupSmarty($this->languages[0], $page, $env);
        $vars = array();
        $previous = set_error_handler(function ($errNo, $errStr) use (&$vars) {
            if (preg_match('/Undefined index: (.+)/', $errStr, $matches)) {
                $vars[$matches[1]] = true;
            }
            return true;
        }, E_NOTICE);
        $smarty->fetch($this->template . '.tpl');
        set_error_handler($previous);
        return array_keys($vars);
    }

    /**
     * Sets up a smarty template.
     *
     * @param $language
     * @param $page
     * @param SystemEnvironmentInterface $env
     * @return \Smarty
     */
    private function setupSmarty($language, $page, SystemEnvironmentInterface $env)
    {
        $smarty = new \Smarty;
        $fs = $env->getFileSystem();
        $bootstrap = $env->getBootstrap();
        $appRoot = $bootstrap->getAppRoot();
        $template_dir = $fs->getRealPath($appRoot . '/templates');
        $smarty->setTemplateDir($template_dir);
        $smarty->setCompileDir($fs->getRealPath($appRoot . '/files/smarty'));
        if ($fs->fileExists($appRoot . '/smartyPlugins')) {
            $smarty->setPluginsDir($fs->getRealPath($appRoot . '/smartyPlugins'));
        }
        $smarty->assign('language', $language);
        $smarty->assign('page', $page);
        $smarty->assign('template_dir', $template_dir);
        $smarty->assign('base_url', $bootstrap->getWebAppUrl());
        $smarty->assign('base_dir', $appRoot);
        $smarty->assign('params', http_build_query($this->params));
        $smarty->assign('instType', $bootstrap->getInstallationType());
        $smarty->assign('genTime', time());
        return $smarty;
    }
}
