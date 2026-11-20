<?php
/**
 * @title Tooltips
 * @description render() wraps content; attributes() adds a tooltip to any other element.
 */
echo $this->Tooltip->render($this->Button->render('Hover me', ['variant' => 'outline']), 'Add to library');
echo $this->Button->render('Save', $this->Tooltip->attributes('Save changes', ['side' => 'bottom']) + ['icon' => 'check']);
echo $this->Tooltip->render($this->Button->icon('settings-2', 'Settings', ['variant' => 'ghost']), 'Settings', ['side' => 'right', 'align' => 'center']);
