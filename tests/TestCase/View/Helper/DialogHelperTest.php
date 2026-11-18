<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\DialogHelper;

class DialogHelperTest extends HelperTestCase
{
    public function testRenderFull(): void
    {
        $dialog = new DialogHelper($this->View);
        $out = $dialog->render('profile', '<form></form>', [
            'title' => 'Edit profile',
            'description' => 'Make changes',
            'footer' => '<button>Save</button>',
            'trigger' => 'Edit',
            'class' => 'w-full sm:max-w-[425px]',
        ]);

        $this->assertStringStartsWith(
            '<button onclick="document.getElementById(&#039;profile&#039;).showModal()" aria-haspopup="dialog" type="button" class="btn" data-variant="outline">Edit</button><dialog id="profile" aria-labelledby="profile-title" aria-describedby="profile-description"',
            $out,
        );
        $this->assertStringContainsString('class="dialog w-full sm:max-w-[425px]"', $out);
        $this->assertStringContainsString('<div><header><h2 id="profile-title">Edit profile</h2><p id="profile-description">Make changes</p></header><section><form></form></section><footer><button>Save</button></footer>', $out);
        $this->assertStringContainsString('aria-label="Close dialog"', $out);
        $this->assertStringContainsString('onpointerdown=', $out);
    }

    public function testOptionsDisableExtras(): void
    {
        $dialog = new DialogHelper($this->View, ['closeButton' => false, 'closeOnOverlay' => false]);
        $this->assertSame(
            '<dialog open="open" id="d" class="dialog"><div><section>x</section></div></dialog>',
            $dialog->render('d', 'x', ['open' => true]),
        );
    }

    public function testTriggerAndClose(): void
    {
        $dialog = new DialogHelper($this->View, ['trigger' => ['variant' => 'secondary']]);
        $this->assertStringContainsString('data-variant="secondary"', $dialog->trigger('d', 'Open'));
        $this->assertStringContainsString('this.closest(&#039;dialog&#039;).close()', $dialog->close('Cancel'));
    }
}
