<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase;

use BasecoatTheme\View\Helper\SidebarHelper;
use Cake\Http\ServerRequest;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;
use Cake\View\View;

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
}
