<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\PageHeaderHelper;

class PageHeaderHelperTest extends HelperTestCase
{
    public function testRender(): void
    {
        $header = new PageHeaderHelper($this->View);
        $out = $header->render('Dashboard', ['description' => 'Overview of your store']);
        $this->assertStringStartsWith('<header class="flex flex-wrap items-center justify-between gap-4">', $out);
        $this->assertStringContainsString('<h1', $out);
        $this->assertStringContainsString('Dashboard</h1>', $out);
        $this->assertStringContainsString('Overview of your store', $out);
    }

    public function testActionsArrayJoined(): void
    {
        $header = new PageHeaderHelper($this->View);
        $out = $header->render('Users', ['actions' => ['<a>One</a>', '<button>Two</button>']]);
        $this->assertStringContainsString('<a>One</a><button>Two</button>', $this->squash($out));
        $this->assertStringContainsString('<a>One</a>', $header->render('Users', ['actions' => '<a>One</a>']));
    }

    public function testBreadcrumb(): void
    {
        $header = new PageHeaderHelper($this->View);
        $out = $header->render('Users', ['breadcrumb' => '<nav>crumbs</nav>']);
        $this->assertStringContainsString('<nav>crumbs</nav>', $out);
    }

    public function testLevel(): void
    {
        $header = new PageHeaderHelper($this->View);
        $out = $header->render('Users', ['level' => 2]);
        $this->assertStringContainsString('<h2', $out);
        $this->assertStringContainsString('</h2>', $out);
    }

    public function testEscapes(): void
    {
        $header = new PageHeaderHelper($this->View);
        $out = $header->render('A <b>', ['description' => '<i>x</i>']);
        $this->assertStringContainsString('A &lt;b&gt;', $out);
        $this->assertStringContainsString('&lt;i&gt;x&lt;/i&gt;', $out);
    }
}
