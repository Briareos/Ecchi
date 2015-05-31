<?php

namespace Ecchi\Assetic\Filter;

use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterInterface;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Twig_Environment;

/**
 * Compiles an AngularJS project directory structure into single JavaScript file.
 * Components should reside in the directories of their type, and they should be one of
 *   - controller
 *   - directive
 *   - factory
 *   - filter
 *   - provider
 *   - service
 *   - template
 * Module name is taken from the main JavaScript file that is loaded using this filter, which is {moduleName}.js.
 * The module must be registered with at least one locale, and the file should be named as {moduleName}-{locale}.js
 * and it should be empty.
 * When rendering the template via assetic, always reference the file with a locale.
 *
 * @package Ecchi\Assetic\Filter
 */
class AngularAppFilter implements FilterInterface
{

    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * @param Twig_Environment $engine
     */
    public function setTwig(Twig_Environment $engine)
    {
        $this->twig = $engine;
    }

    /**
     * {@inheritdoc}
     */
    public function filterLoad(AssetInterface $asset)
    {
        $dir = dirname($asset->getSourceRoot().'/'.$asset->getSourcePath());

        if (!is_dir($dir.'/module')) {
            // This is not the main module file, bail out.
            $asset->setContent('');

            return;
        }

        $fileName   = $this->getFilename(basename($asset->getSourcePath()));
        $moduleName = $fileName;
        $locale     = null;

        if (strpos($fileName, '-') !== false) {
            list($moduleName, $locale) = explode('-', $fileName);
        }

        $content = file_get_contents($dir.'/module/'.$moduleName.'.js');

        $jsFinder = (new Finder())
            ->in($dir.'/controller')
            ->in($dir.'/directive')
            ->in($dir.'/factory')
            ->in($dir.'/filter')
            ->in($dir.'/provider')
            ->in($dir.'/service')
            ->name('*.{js,js.twig}');

        /** @var SplFileInfo $componentFile */
        foreach ($jsFinder->files() as $componentFile) {
            $componentType = basename(dirname($componentFile->getRealPath()));
            $content .= "\n".$this->compileComponent($moduleName, $componentType, $componentFile, $locale);
        }
        $templateFinder = (new Finder())
            ->in($dir.'/template')
            ->name('*.{html,html.twig}');

        $templates = [];
        /** @var SplFileInfo $templateFile */
        foreach ($templateFinder->files() as $templateFile) {
            $templateName             = $templateFile->getFilename();
            $templates[$templateName] = $templateFile;
        }
        $content .= "\n".$this->compileTemplates($moduleName, $templates, $locale);

        $asset->setContent(trim($content));
    }

    /**
     * {@inheritdoc}
     */
    public function filterDump(AssetInterface $asset)
    {
    }

    /**
     * Returns file name without the extension, with it being everything until the first dot.
     *
     * @param string $path
     *
     * @return string
     */
    private function getFilename($path)
    {
        return explode('.', $path, 2)[0];
    }

    /**
     * Returns file extension without the filename, with it being everything after the first dot.
     *
     * @param string $path
     *
     * @return string
     */
    private function getExtension($path)
    {
        if (strpos($path, '.') === false) {
            return '';
        }

        return explode('.', $path, 2)[1];
    }

    /**
     * @param string      $moduleName
     * @param string      $componentType
     * @param SplFileInfo $component
     * @param string      $locale
     *
     * @return string
     */
    private function compileComponent($moduleName, $componentType, SplFileInfo $component, $locale = null)
    {
        $template      = <<<'EOF'
angular.module('%s').%s('%s', %s);

EOF;
        $componentName = $this->getFilename($component->getFilename());

        $compiled = sprintf($template, $moduleName, $componentType, $componentName, $this->render($component, $locale));

        return $compiled;
    }

    /**
     * @param string        $moduleName
     * @param SplFileInfo[] $templates
     * @param null          $locale
     *
     * @return string
     */
    private function compileTemplates($moduleName, $templates, $locale = null)
    {
        $compiled = sprintf(
            <<<'EOF'
angular.module('%s').run(['$templateCache', function ($templateCache) {

EOF
            ,
            $moduleName
        );
        foreach ($templates as $templateName => $template) {
            $html = $this->render($template, $locale);
            $compiled .= sprintf(
                <<<'EOF'
    $templateCache.put('%s', %s);

EOF
                ,
                $this->getFilename($templateName).'.html',
                $html
            );
        }
        $compiled .= <<<'EOF'
}]);

EOF;

        return $compiled;
    }

    /**
     * Renders content depending on the file's extension.
     *
     * @param SplFileInfo $file
     * @param null        $locale
     *
     * @return string
     * @throws \Exception
     */
    private function render(SplFileInfo $file, $locale = null)
    {
        switch ($this->getExtension($file->getFilename())) {
            case 'js':
                return $this->filterJs($file->getContents());
                break;
            case 'html':
                return $this->filterHtmlTemplate($file->getContents());
                break;
            case 'js.twig':
                return $this->filterJs($this->renderTwig($file->getContents(), $locale));
                break;
            case 'html.twig':
                return $this->filterHtmlTemplate($this->renderTwig($file->getContents(), $locale));
                break;
            default:
                throw new \Exception('File extension "%s" is not supported', $file->getExtension());
        }
    }

    /**
     * Renders a string via twig. Also sets the locale for the translator extension if it's registered.
     *
     * @param string $contents Raw content to render.
     * @param string $locale   A valid locale for translator to use.
     *
     * @return string     Rendered content.
     * @throws \Exception If the twig environment is not set.
     */
    private function renderTwig($contents, $locale = null)
    {
        if ($this->twig === null) {
            throw new \Exception(sprintf('Twig template renderer is not registered'));
        }

        if ($locale !== null && $this->twig->hasExtension('translator') && $this->twig->getExtension('translator') instanceof TranslationExtension) {
            /** @var TranslationExtension $translatorExtension */
            $translatorExtension = $this->twig->getExtension('translator');
            $translatorExtension->getTranslator()->setLocale($locale);
        }

        return $this->twig->render($contents);
    }

    /**
     * Escapes HTML into a safe JS string representation.
     *
     * @param string $string
     *
     * @return string
     */
    private function filterHtmlTemplate($string)
    {
        $string = json_encode($string);

        return $string;
    }

    /**
     * Strip JavaScript comments, trim whitespace and semicolons.
     * This is absolutely not for edge cases and is only for internal use.
     *
     * @param string $string
     *
     * @return string
     * @link http://stackoverflow.com/a/15123777
     */
    private function filterJs($string)
    {
        return trim(trim(preg_replace('/(?:\/\*(?:[\s\S]*?)\*\/)|(?:[\s;]+\/\/(?:.*)$)/m', '', $string)), '();');
    }

    public function __sleep()
    {
        return [];
    }
}