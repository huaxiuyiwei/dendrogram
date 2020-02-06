<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/14 0014
 * Time: 下午 2:40
 */

namespace DenDroGram\Controller;

use DenDroGram\Helpers\Func;
use DenDroGram\Model\AdjacencyListModel;
use DenDroGram\ViewModel\AdjacencyListCatalogViewModel;
use DenDroGram\ViewModel\AdjacencyListRhizomeViewModel;

class AdjacencyList implements Structure
{
    /**
     * @param $id
     * @param $router
     * @param array $column
     * @return mixed|string
     */
    public function buildCatalog($id, $router, array $column = ['name'])
    {
        $css = file_get_contents(__DIR__ . '/../Static/dendrogram.css');
        $js = file_get_contents(__DIR__ . '/../Static/dendrogram.js');
        $js = sprintf($js, $router);

        $data = AdjacencyListModel::getChildren($id);
        $html = (new AdjacencyListCatalogViewModel($column))->index($data);
        $view = <<<EOF
<style>%s</style>
<script>%s</script>
%s
<div id="mongolia"></div>
<script>dendrogram.tree.init();</script>
EOF;
        return sprintf($view, $css, $js, $html);
    }

    /**
     * @param $id
     * @param $router
     * @param array $column
     * @return mixed|string
     */
    public function buildRhizome($id, $router, array $column = ['name'])
    {
        $css = file_get_contents(__DIR__ . '/../Static/dendrogram.css');
        $js = file_get_contents(__DIR__ . '/../Static/dendrogram.js');
        $js = sprintf($js, $router);

        $data = AdjacencyListModel::getChildren($id);
        $html = (new AdjacencyListRhizomeViewModel($column))->index($data);
        $view = <<<EOF
<style>%s</style>
<script>%s</script>
<div class="dendrogram dendrogram-rhizome dendrogram-animation-fade">
%s
</div>
<div id="mongolia"></div>
<script>dendrogram.tree.init();</script>
EOF;
        return sprintf($view, $css, $js, $html);
    }

    public function buildSelect($id, $label, $value)
    {
        $css = file_get_contents(__DIR__ . '/../Static/dendrogramUnlimitedSelect.css');
        $js = file_get_contents(__DIR__ . '/../Static/dendrogramUnlimitedSelect.js');
        $js = sprintf($js, $label, $value);
        $tree = json_encode($this->getTreeData($id));
        $view = <<<EOF
<style>%s</style>
<div id="dendrogram-unlimited-select"></div>
<script>%s dendrogramUS.create(%s);</script>
EOF;
        return sprintf($view, $css, $js, $tree);
    }

    /**
     * @param $id
     * @return array
     */
    public function getTreeData($id)
    {
        $data = AdjacencyListModel::getChildren($id, 'DESC');
        return self::makeTeeData($data);
    }

    private static function makeTeeData($data)
    {
        $tempDeepArray = [];
        foreach ($data as $item) {
            $item['children'] = [];
            $tempDeepArray[$item['layer']][$item['p_id']][] = $item;
        }

        foreach ($tempDeepArray as $layer => $boundary) {
            $nextLayer = $layer - 1;
            foreach ($tempDeepArray[$layer] as $p_id => $list) {
                if (!isset($tempDeepArray[$nextLayer])) {
                    break;
                }

                foreach ($tempDeepArray[$nextLayer] as $b_k => $nextBoundaryList) {
                    foreach ($nextBoundaryList as $i_k => $item) {
                        if(empty($tempDeepArray[$layer])){
                            break(2);
                        }
                        if ($item['id'] !== $p_id) {
                            continue;
                        }
                        $tempDeepArray[$nextLayer][$b_k][$i_k]['children'] = $list;
                        unset($tempDeepArray[$layer][$p_id]);
                    }
                }
            }
        }
        return current(current(current(array_filter($tempDeepArray))));
    }

    /**
     * @param $action
     * @param $data
     * @return bool
     */
    public function operateNode($action, $data)
    {
        if ($action == 'add') {
            $parent = AdjacencyListModel::where('id', $data['p_id'])->first();
            if (!$parent) {
                $data['layer'] = 0;
            } else {
                $data['layer'] = $parent->layer + 1;
            }
            return AdjacencyListModel::insertGetId($data);
        } elseif ($action == 'update' && isset($data['id'])) {
            return AdjacencyListModel::where('id', $data['id'])->update($data);
        } elseif ($action == 'delete' && isset($data['id'])) {
            return AdjacencyListModel::deleteAll($data['id']);
        }
        return false;
    }

}
