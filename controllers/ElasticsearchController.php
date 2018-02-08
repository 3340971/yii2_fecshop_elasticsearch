<?php
/**
 * FecShop file.
 *
 * @link http://www.fecshop.com/
 * @copyright Copyright (c) 2016 FecShop Software LLC
 * @license http://www.fecshop.com/license/
 */

namespace fecshop\elasticsearch\controllers;

use Yii;
use yii\console\Controller;
use fecshop\elasticsearch\models\elasticSearch\Product as EsProduct;
/**
 * @author Terry Zhao <2358269014@qq.com>
 * @since 1.0
 */
class ElasticsearchController extends Controller
{
    protected $_numPerPage = 50;

    /**
     * ͨ���ű����Ѳ�Ʒ����Ӧ������Ϣ��ӵ���Ӧ���±��С�
     * Ȼ������Ӧ����������ʱ���Զ�ȥ��Ӧ�ı��в����ݡ�
     * Ϊʲô��Ҫ����ô�����?��Ϊmongodb��ȫ��������fullSearch����������һ������ֻ����һ����
     * ������������Ƕ���ֶε����������
     * ��ˣ����ڶ����ԵĲ�Ʒ��������Ҫ�㼸�����ˡ�
     * ����Ĺ��ܣ�
     * 1. ����Ʒ��name description  price img score sku  spu����Ϣ���¹�����

     */
    public function actionSyncproduct($pageNum)
    {
        $filter['numPerPage'] = $this->_numPerPage;
        $filter['pageNum'] = $pageNum;
        $filter['asArray'] = true;
        $products = Yii::$service->product->coll($filter);
        $product_ids = [];
        $langs = Yii::$service->fecshoplang->getAllLangCode();
        
        foreach ($products['coll'] as $one) {
            //var_dump($one);
            //exit;
            foreach ($langs as $langCode) {
                $_id = $one['_id'];
                $name = Yii::$service->fecshoplang->getLangAttrVal($one['name'], 'name', $langCode); 
                $description = Yii::$service->fecshoplang->getLangAttrVal($one['description'], 'description', $langCode); 
                $short_description = Yii::$service->fecshoplang->getLangAttrVal($one['short_description'], 'short_description', $langCode); 
                
                $spu = $one['spu'];
                $sku = $one['sku'];
                $score = $one['score'];
                $status = $one['status'];
                $is_in_stock = $one['is_in_stock'];
                $url_key = $one['url_key'];
                $price = $one['price'];
                $cost_price = $one['cost_price'];
                $special_price = $one['special_price'];
                $special_from = $one['special_from'];
                $special_to = $one['special_to'];
                $image = serialize($one['image']);
                $created_at = $one['created_at'];
                $sync_updated_at = $one['sync_updated_at'];
                $final_price = Yii::$service->product->price->getFinalPrice($price, $special_price, $special_from, $special_to);
                
                EsProduct::initLang($langCode);
                $esOne = EsProduct::findOne($_id);
                if (!$esOne['sku']) {  // !$esOne->getPrimaryKey()
                    $esOne = new EsProduct;
                    $esOne['_id'] = $_id;
                }
                $esOne['name'] = $name;
                $esOne['description'] = $description;
                $esOne['short_description'] = $short_description;
                $esOne['spu'] = $spu;
                $esOne['sku'] = $sku;
                $esOne['score'] = $score;
                $esOne['status'] = $status;
                $esOne['is_in_stock'] = $is_in_stock;
                $esOne['url_key'] = $url_key;
                $esOne['price'] = $price;
                $esOne['cost_price'] = $cost_price;
                $esOne['special_price'] = $special_price;
                $esOne['special_from'] = $special_from;
                $esOne['special_to'] = $special_to;
                $esOne['image'] = $image;
                $esOne['created_at'] = $created_at;
                $esOne['sync_updated_at'] = $sync_updated_at;
                $esOne['final_price'] = $final_price;
                $esOne->save();
            }
        }
    }
    
    public function actionClean(){
        Yii::$service->search->elasticSearch->esDeleteAllProduct();
        
    }

    /**
     * �õ���Ʒ��������
     */
    public function actionProductcount()
    {
        $count = Yii::$service->product->collCount($filter);
        echo $count;
    }

    public function actionProductpagenum()
    {
        $count = Yii::$service->product->collCount($filter);
        echo ceil($count / $this->_numPerPage);
    }
    
    public function actionUpdatemapping(){
        Yii::$service->search->elasticSearch->updateMapping();
        
    }
}
