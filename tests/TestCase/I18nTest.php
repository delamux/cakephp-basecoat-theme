<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase;

use BasecoatTheme\View\Helper\ButtonHelper;
use BasecoatTheme\View\Helper\ConversationHelper;
use BasecoatTheme\View\Helper\DateRangeFieldHelper;
use BasecoatTheme\View\Helper\SidebarHelper;
use Cake\Http\ServerRequest;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use AppendIterator;
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

    public function testPluralAndDateRangeStrings(): void
    {
        $this->loadPlugins(['BasecoatTheme']);
        I18n::setLocale('es_ES');
        $view = new View(new ServerRequest());
        $button = new ButtonHelper($view);
        $this->assertStringContainsString('<span class="sr-only">1 nuevo</span>', $button->render('Bandeja', ['indicator' => 1]));
        $this->assertStringContainsString('<span class="sr-only">3 nuevos</span>', $button->render('Bandeja', ['indicator' => 3]));
        $range = (new DateRangeFieldHelper($view))->render('created', ['label' => 'Creado', 'id' => 'dr']);
        $this->assertStringContainsString('>Creado hasta</label>', $range);
    }

    public function testEverySourceStringIsInTheCatalogs(): void
    {
        $root = dirname(__DIR__, 2);
        $strings = [];
        $plurals = [];
        $files = new AppendIterator();
        foreach (['/src', '/templates'] as $dir) {
            $files->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . $dir)));
        }
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $source = (string)file_get_contents($file->getPathname());
                preg_match_all("/__d\('basecoat_theme', '((?:[^'\\\\]|\\\\.)*)'/", $source, $m);
                $strings = array_merge($strings, $m[1]);
                preg_match_all("/__dn\('basecoat_theme', '((?:[^'\\\\]|\\\\.)*)', '((?:[^'\\\\]|\\\\.)*)'/", $source, $m);
                $strings = array_merge($strings, $m[1]);
                foreach ($m[2] as $plural) {
                    $plurals[] = $plural;
                }
            }
        }
        $this->assertNotEmpty($strings);
        foreach (['basecoat_theme.pot', 'es/basecoat_theme.po'] as $catalog) {
            $contents = (string)file_get_contents($root . '/resources/locales/' . $catalog);
            foreach (array_unique($strings) as $string) {
                $this->assertStringContainsString('msgid "' . $string . '"', $contents, $catalog . ': ' . $string);
            }
            foreach (array_unique($plurals) as $plural) {
                $this->assertStringContainsString('msgid_plural "' . $plural . '"', $contents, $catalog . ': ' . $plural);
            }
        }
    }
}
