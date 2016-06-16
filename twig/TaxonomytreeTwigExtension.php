<?php
namespace Grav\Plugin;

use Grav\Common\Grav;
use Grav\Common\Filesystem\Folder;

class TaxonomytreeTwigExtension extends \Twig_Extension
{
    /**
     * Returns extension name.
     *
     * @return string
     */
    public function getName()
    {
        return 'TaxonomytreeTwigExtension';
    }

    /**
     * Return a list of all filters.
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('taxonomytree', [$this, 'taxonomytreeFunction']),
        ];
    }

    public function taxonomytreeFunction($path = '') {
        $grav = Grav::instance();
        //$cache = $grav['cache'];
        $taxonomy = $grav['taxonomy'];
        $map = $taxonomy->taxonomy();

        if(is_array($path))
        {
            $path = implode('/', $path);
        }

        return $this->filterTaxonomyMap($map, $path);
    }

    /**
     * Filter a Taxonomy-Map so it only contains Taxonomy-Items used below a
     * specific Path.
     *
     * Expects a Map with the following Structure, as produced by
     * Grav\Common\Taxonomy::taxonomy()
     *
     * array [
     *   "category" => array [
     *     "web" => array [
     *       "/home/peter/Misc/somesite/user/pages/03.design/some-project" => array:1 [
     *         "slug" => "some-project"
     *       ]
     *       "/home/peter/Misc/somesite/user/pages/03.design/another-project" => array:1 […]
     *     ]
     *     "landscape" => array […]
     *     "free project" => array […]
     *   ]
     * ]
     *
     * Returns the same Format, but with all Taxonomy-Items filtered out that are
     * not used in the specified Path.
     *
     * @param  array  $map  taxonomy map as produced by as produced by 
     *                      Grav\Common\Taxonomy::taxonomy()
     * @param  array  $path path-components ("root/subpage/subsubpage")
     * @return array        filtered taxonomy map
     */
    private function filterTaxonomyMap($map, $path)
    {
        // shortcut for non-filtered map
        if(strlen($path) == 0)
            return $map;

        $filtered = [];

        // $axis = 'category'; // the name of the taxonomy-field
        // $values = ['landscape' => […]] // one of the values used in this field
        foreach ($map as $axis => $values)
        {
            // $value = ''landscape'; // one of the values used in this field
            // $pages = ['/home/peter/Misc/somesite/user/pages/03.design/another-project' => […]] // pages using this value
            foreach ($values as $value => $pages)
            {
                // $pagepath = '/home/peter/Misc/somesite/user/pages/03.design/another-project'; // path to the file usind this value
                // $page = ["slug" => "some-project"] // some data about this page
                foreach ($pages as $pagepath => $page)
                {
                    // convert the absolute basepath to a logical
                    $logical = $this->absoluteToLogicalPath($pagepath);

                    // filter patching paths out
                    if(strpos($logical, $path) !== 0)
                        continue;

                    // record remaining information
                    $filtered[$axis][$value][$pagepath] = $page;
                }
            }
        }

        return $filtered;
    }

    /**
     * Converts an absolute Path as returned by Grav\Common\Taxonomy::taxonomy()
     * to a locical path (by removing …/user/pages) and stripping of the numeric
     * prefixes.
     *
     * @param  string $abspath absolute path (/home/peter/Misc/somesite/user/pages/03.design/some-project)
     * @return string          logcal path (design/some-project)
     */
    private function absoluteToLogicalPath($abspath)
    {
        $replath = Folder::getRelativePath($abspath);
        Folder::shift($replath);
        Folder::shift($replath);
        $replath = trim($replath, '/');
        $segments = explode('/', $replath);

        foreach($segments as &$segment) {
            $segment = preg_replace(PAGE_ORDER_PREFIX_REGEX, '', $segment);
        }

        return implode('/', $segments);
    }
}
