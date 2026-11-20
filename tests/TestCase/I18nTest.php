<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase;

use BasecoatTheme\View\Helper\ConversationHelper;
use BasecoatTheme\View\Helper\SidebarHelper;
use Cake\Http\ServerRequest;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class I18nTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        I18n::setLocale(I18n::getDefaultLocale());
        $this->clearPlugins();
    }

    public function testSpanishTranslations(): void
    {
        $this->loadPlugins(['BasecoatTheme']);
        I18n::setLocale('es_ES');
        $sidebar = new SidebarHelper(new View(new ServerRequest()));
        $this->assertStringContainsString('aria-label="Mostrar u ocultar menú"', $sidebar->toggle());
    }

    public function testDashboardHelpersAreTranslated(): void
    {
        $this->loadPlugins(['BasecoatTheme']);
        I18n::setLocale('es_ES');
        $html = (new ConversationHelper(new View(new ServerRequest())))->render([]);
        $this->assertStringContainsString('aria-label="Mensaje"', $html);
        $this->assertStringContainsString('>Enviar</button>', $html);
    }

    public function testEverySourceStringIsInTheCatalogs(): void
    {
        $root = dirname(__DIR__, 2);
        $strings = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src'));
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                preg_match_all("/__d\('basecoat_theme', '((?:[^'\\\\]|\\\\.)*)'/", (string)file_get_contents($file->getPathname()), $m);
                $strings = array_merge($strings, $m[1]);
            }
        }
        $this->assertNotEmpty($strings);
        foreach (['basecoat_theme.pot', 'es/basecoat_theme.po'] as $catalog) {
            $contents = (string)file_get_contents($root . '/resources/locales/' . $catalog);
            foreach (array_unique($strings) as $string) {
                $this->assertStringContainsString('msgid "' . $string . '"', $contents, $catalog . ': ' . $string);
            }
        }
    }
}
