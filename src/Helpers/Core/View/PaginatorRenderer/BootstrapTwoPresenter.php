<?php namespace CoasterCms\Helpers\Core\View\PaginatorRenderer;

use Illuminate\Pagination\BootstrapThreePresenter;

class BootstrapTwoPresenter extends BootstrapThreePresenter
{

    public function render()
    {
        if (!$this->hasPages())
            return '';

        return sprintf(
            '<div class="pagination"><ul>%s %s %s</ul></div>',
            $this->getPreviousButton(),
            $this->getLinks(),
            $this->getNextButton()
        );
    }

}