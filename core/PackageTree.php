<?php
final class PackageTree
{
    use log;
    use numberConvertor;

    public function buildFinalArray($packages, $parentId)
    {
        $trees = $this->_buildTree($packages, $parentId);
        return $this->_buildArrays($trees);
    }

    private function _buildTree($packages, $parentId)
    {
        $tree = [];

        foreach ($packages as $package) {
            if ($package['parent_id'] == $parentId) {
                $children = self::_buildTree($packages, $package['id']);
                if ($children) {
                    $package['children'] = $children;
                }
                $tree[] = $package;
            }
        }

        return $tree;
    }

    private function _buildArrays($packages)
    {
        $tree = [];

        foreach ($packages as $package) {
            if ($package['children']) {
                if (isset($package['children'][0]['children'])) {
                    $tree[$package['title']] = $this->_buildArrays($package['children']);
                } else {
                    $res = [];
                    foreach ($package['children'] as $row) {
                        $res[$row['id']] = $row['price'];
                    }
                    $tree[$package['title']] = $res;
                }
            }
        }

        return $tree;
    }

    private function _addTd($title, $style)
    {
        return '<td style="' . $style . '">' . $title . '</td>';
    }

    private function _addTr(...$data)
    {
        $tr = '';
        foreach ($data as $row)
            $tr .= $row;
        return '<tr>' . $tr . '</tr>';
    }

    private function _addHeaderTd($title)
    {
        return $this->_addTd($title, 'padding:5px;background-color:#2f5496;color:#FFFFFF;border:5px solid #2f5496;');
    }

    private function _addBodyTd($title)
    {
        return $this->_addTd($title, 'padding:5px;background-color:#d9d9d9;color:#000000;border:5px solid #d9d9d9;');
    }

    private function _tableHeader()
    {
        $numberOfUsers = $this->_addHeaderTd(PDFTextContext::get('numberOfUsers'));
        $traffic = $this->_addHeaderTd(PDFTextContext::get('traffic'));
        $oneMonthPrice = $this->_addHeaderTd(PDFTextContext::get('oneMonthPrice'));
        $threeMonthPrice = $this->_addHeaderTd(PDFTextContext::get('threeMonthPrice'));
        $sixMonthPrice = $this->_addHeaderTd(PDFTextContext::get('sixMonthPrice'));

        return $this->_addTr($numberOfUsers, $traffic, $oneMonthPrice, $threeMonthPrice, $sixMonthPrice);
    }

    private function _tableBody($trees, $user_packages)
    {
        $body = '';
        foreach ($trees as $keyTree => $tree) {
            foreach ($tree as $keyThisPackages => $thisPackages) {
                $tr = [];
                $tr[] = $this->_addBodyTd(numberConvertor::englishNumberTopersian($keyTree));
                $tr[] = $this->_addBodyTd(numberConvertor::englishNumberTopersian($keyThisPackages));
                foreach ($thisPackages as $keyPackage => $package) {
                    $price = isset($user_packages[$keyPackage]) ? number_format($user_packages[$keyPackage]) : number_format($package);
                    $tr[] = $this->_addBodyTd(numberConvertor::englishNumberTopersian($price));
                }
                $body .= $this->_addTr(...$tr);
            }
        }
        return $body;
    }

    public function generateTable($packages, $user_packages)
    {
        $trees = $this->buildFinalArray($packages, 0);
        if (ConfigContext::get('debug_mode'))
            $this::log($trees, 'tree2.html');

        $tableHeader = $this->_tableHeader();
        $tableBody = $this->_tableBody($trees, $user_packages);

        $table = '<table dir="rtl" cellspacing="10" cellpadding="5" border="0" align="center">';

        $table .= $tableHeader;
        $table .= $tableBody;

        $table .= '</table>';

        return $table;
    }
}