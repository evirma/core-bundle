<?php

namespace Evirma\Bundle\CoreBundle\Pager\Template;

use Evirma\Bundle\CoreBundle\Pager\Pager;

class PagerTemplateBem extends AbstractPagerTemplate
{
    /**
     * @var Pager
     */
    protected Pager $pager;

    protected array $options = [
        'proximity' => 6,
        'proximity_on_mobile' => 3,
        'max_index_pages' => 100,
        'show_digit_pages' => true,
        'next_page_button_prepend' => false,
        'show_next_page_button' => false,
    ];

    private int $proximity;
    private int $proximityOnMobile;
    private int $page;
    private int $pages;
    private int $startPage;
    private int $endPage;
    private int $startPageMobile;
    private int $endPageMobile;

    public function render(Pager $pager, $routeGenerator, array $options = [])
    {
        if ($pager->getPages() <= 1) {
            return '';
        }

        $this->pager = $pager;
        $this->options = array_merge($this->options, $options);

        $this->proximity = $this->option('proximity');
        $this->proximityOnMobile = $this->option('proximity_on_mobile');
        $this->page = $pager->getPage();
        $this->pages = $pager->getPages();
        $this->calculateStartAndEndPage();
        $this->setRouteGenerator($routeGenerator);

        $result = '<div class="pager">';

        $nextPage = $this->page + 1;
        if ($nextPage <= $this->pages) {
            $result .= $this->next($nextPage);
        }

        if ($this->option('show_digit_pages')) {
            $navLabel = ($this->locale == 'ru') ? 'Постраничная навигаци' : 'Pagination Navigation';
            $result .= '<nav class="pager__nav" aria-label="'. $navLabel . '"><ul class="pager__pages">';
            $result .= $this->first();

            if ($this->startPage > 1) {
                $result .= $this->separator();
            }

            for ($p = $this->startPage; $p <= $this->endPage; $p++) {
                $isHidden = ($p < $this->startPageMobile) || ($p > $this->endPageMobile);
                $result .= $this->page($p, $isHidden);
            }

            if ($this->endPage < $this->pages) {
                $result .= $this->separator();
                $result .= $this->page($this->pages);
            }

            $result .= '</ul></nav>';
        }

        $result .= '</div>';

        return $result;
    }

    public function next($page)
    {
        if (!$this->option('show_next_page_button')) {
            return '';
        }

        $href = $this->generateRoute($page < 1 ? 1 : $page);
        $maxIndexPages = $this->option('max_index_pages');
        $rel = ($page > $maxIndexPages) ? ' rel="noindex,nofollow"' : '';

        $nextPageButtonPrepend = $this->option('next_page_button_prepend');

        $nextPageText = ($this->locale == 'ru') ? 'Следующая страница' : 'Show More';
        $navNextLabel = ($this->locale == 'ru') ? 'Быстрая навигация' : 'Fast Navigation';

        return "<nav class=\"page__nav-next\" aria-label=\"{$navNextLabel}\">{$nextPageButtonPrepend}<a$rel href=\"{$href}\" class=\"page__next-link\">{$nextPageText} &rarr;</a></nav>";
    }

    public function separator()
    {
        return '<li class="pager__dots">…</li>';
    }

    public function getName()
    {
        return 'default';
    }

    private function first()
    {
        if ($this->startPage > 1) {
            return $this->page(1);
        }

        return '';
    }

    private function page($page, $isHidden = false)
    {
        $hiddenClass = $isHidden ? ' hidden-xs' : '';

        if ($this->locale == 'ru') {
            $text = '<span class="sr-only">Страница №</span>'.$page;
        } else {
            $text = '<span class="sr-only">Page </span>'.$page;
        }
        $href = $this->generateRoute($page < 1 ? 1 : $page);

        $maxIndexPages = $this->option('max_index_pages');

        $rel = ($page > $maxIndexPages) ? ' rel="noindex,nofollow"' : '';

        if ($page == $this->page) {
            $result = '<li class="pager__page pager__page-active'.$hiddenClass.'" aria-current="page"><span>'.$text.'</span></li>';
        } else {
            $result = '<li class="pager__page'.$hiddenClass.'"><a'.$rel.' href="'.$href.'">'.$text.'</a></li>';

        }
        return $result;
    }

    public function current($page)
    {
        $pageText = $this->locale == 'ru' ? 'Страница ' : 'Page ';
        $text = "<span class=\"sr-only\">{$pageText}</span>" . trim($page.' '.$this->option('active_suffix'));
        return '<li class="pager__page pager__page-active"><span>'.$text.'</span></li>';
    }


    private function calculateStartAndEndPage()
    {
        $startPage = $this->page - $this->proximity;
        $endPage = $this->page + $this->proximity;

        if ($startPage < 1) {
            $endPage = min($endPage + (1 - $startPage), $this->pages);
            $startPage = 1;
        }

        if ($endPage > $this->pages) {
            $startPage = max($startPage - ($endPage - $this->pages), 1);
            $endPage = $this->pages;
        }

        $this->startPage = $startPage;
        $this->endPage = $endPage;

        $this->calculateMobileStartAndEndPage();
    }

    private function calculateMobileStartAndEndPage()
    {
        $startPage = $this->page - $this->proximityOnMobile;
        $endPage = $this->page + $this->proximityOnMobile;

        if ($startPage < 1) {
            $endPage = min($endPage + (1 - $startPage), $this->pages);
            $startPage = 1;
        }

        if ($endPage > $this->pages) {
            $startPage = max($startPage - ($endPage - $this->pages), 1);
            $endPage = $this->pages;
        }

        $this->startPageMobile = $startPage;
        $this->endPageMobile = $endPage;
    }

}
