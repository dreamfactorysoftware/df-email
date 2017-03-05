<?php
namespace DreamFactory\Core\Email\Components;


class Mailer extends \Illuminate\Mail\Mailer
{
    use EmailUtilities;

    /**
     * Render the given view.
     *
     * @param  string $view
     * @param  array  $data
     *
     * @return mixed
     */
    protected function renderView($view, $data)
    {
        try {
            return parent::renderView($view, $data);
        } catch (\InvalidArgumentException $e) {
            return static::applyDataToView($view, $data);
        }
    }
}