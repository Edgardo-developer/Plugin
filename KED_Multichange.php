<?php
/**
 * 
 * Class: KED_Multichange
 * Description: Get CSV rows. Every rows contains product category and variations. Changes the products price.
 * 
 */
class KED_Multichange
{
    public $open;
    private $arg1 = 'thickness';
    private $arg2 = 'width';
    private $arg3 = 'types';
    public $cat; 
    public function __construct($file){
        $this->file = $file;
    }

    /**
     * 
     * Class: ked_query
     * Description: gets all products which were written in the CSV
     * 
     */
    public function ked_query(){
        $args_product = array(
            'post_type'     => 'product',
            'tax_query'     => $this->ked_productVarMeta(),
        );
        $args_var_product = array(
            'post_type'     => 'product_variation',
            'meta_query'    => self::ked_productMeta(),
        );
        $query_product = new WP_Query( $args_product );
        $query_var_product = new WP_Query( $args_var_product );
        $query = new WP_Query();
        $query->posts = array_merge($query_product->posts, $query_var_product->posts);
        if (!is_array($query->posts) || count($query->posts) == 0) {
            echo "<br>";
            echo "<h3>No products</h3>";
        }else{
            foreach ($query as $key => $value) {
                if (is_array($value) && count($value) > 0) {
                    foreach ($value as $k => $v) {
                        $product_object = wc_get_product($v->ID);
                        if ($product_object->is_type('variation') || $product_object->is_type('simple')) {
                            KED_Multichange::ked_change_price($product_object);
                        }
                    }
                }
            }
        }
    }

    /**
     * 
     * Class: ked_open
     * Description: test the file which was uploaded
     * 
     */
    private function ked_open(){
        if (preg_match("#.csv#", $this->file) == true) {
            $open = fopen($this->file, 'r');
            return $open;
        }else{
            return false;
        }
    }

    /**
     * 
     * Class: getCsvRows
     * Description: get rows of CSV
     * 
     */
    private function getCsvRows(){
        $data_val = [];
        $ink = 0;
        $row = 1;
        if (($handle = $this->ked_open()) !== FALSE) {
            while (($data = fgetcsv($handle, ",")) !== FALSE) {
                $num = count($data);
                ++$row;
                for ($i=0; $i < $num; $i++) {
                    if ($ink == 1 && $i == 0) {
                        $this->cat = $data[0];
                    }
                    if ($i !== 0 && $ink !== 0) {
                        $data_val[$ink-1][] = $data[$i];
                    }
                }
                ++$ink;
            }
            fclose($handle);
        }
        return $data_val;
    }
    
    /**
     * 
     * Class: getTaxArray
     * Description: begin process attributes
     * 
     */
    private static function getTaxArray(){
        $dates = self::getCsvRows();
        $csvTax = [];
        foreach ($dates as $key => $value) {
            foreach ($value as $k => $v) {
                if ($k == 0) {
                    $csvTax[0][] = $v;
                }else if($k == 1){
                    $csvTax[1][] = $v;
                }else if($k == 2){
                    $csvTax[2][] = $v;
                }
            }
        }

        $tax_unic = [];
        $tax_unic[1] = array_values(array_unique($csvTax[0], SORT_REGULAR));
        $tax_unic[2] = array_values(array_unique($csvTax[1], SORT_REGULAR));
        $tax_unic[3] = array_values(array_unique($csvTax[2], SORT_REGULAR));
        return $tax_unic;
    }

    /**
     * 
     * Class: ked_productVarMeta
     * Description: meta_query for product_variation
     * 
     */
    private function ked_productVarMeta(){
        $taxonomies = self::getTaxArray();
        $tax_query = array( 'relation' => 'AND', );
        $args = array(
            'relation'  => 'AND',
            array(
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $this->cat,
            ),
            array(
                'relation'  => 'OR',
            ),
        );
        $i = 0; 
        foreach ($taxonomies as $key => $value) {
            if ($i == 0) {
                $tax_name = "pa_".$self::$arg1;
            }else if($i == 1){
                $tax_name = "pa_".$self::$arg2;
            }else if($i == 2){
                $tax_name = "pa_".$self::$arg3;
            }
            $tax_value = array();
            foreach ($value as $k => $v) {
                $tax_value[] = $v;
            }
            $new = array(
                'taxonomy'  => $tax_name,
                'field'    => 'slug',
                'terms'    => $tax_value,
            );
            array_push($args[1], $new);
            ++$i;
        }
        $tax_query[] = $args;
        return $tax_query;
    }

    /**
     * 
     * Class: ked_productMeta
     * Description: tax_query for product
     * 
     */
    private static function ked_productMeta(){
        $unic_tax = self::ked_unic_tax();
        $tax_query = array( 'relation' => 'AND', );
        $args = array(
            'relation'  => 'AND',
            array(
                'relation'  => 'OR',
            ),
        );
        $i = 0; 
        foreach ($unic_tax as $key => $value) {
            if ($i == 0) {
                $tax_name = "attribute_pa_".self::$arg1;
            }else if($i == 1){
                $tax_name = "attribute_pa_".self::$arg2;
            }else if($i == 2){
                $tax_name = "attribute_pa_".self::$arg3;
            }
            $tax_value = array();
            foreach ($value as $k => $v) {
                $tax_value[] = $v;
            }
            $new = array(
                'key'  => $tax_name,
                'value'    => $tax_value,
            );
            array_push($a[0], $new);
            ++$i;
        }
        $tax_query[] = $args;
        return $tax_query;
    }
    
     /**
     * 
     * Class: ked_change_price
     * Description: update price of product
     * 
     */
    private function ked_change_price($product){
        $ked_k_v = KED_Multichange::ked_sort();
        $arg1 = $product->get_attribute(self::$arg1);
        $arg2 = $product->get_attribute(self::$arg2);
        $arg3 = $product->get_attribute(self::$arg3);
        if (null !== $arg1 && "" !== $arg1 && null !== $arg2 && "" !== $arg2 && null !== $arg3 && "" !== $arg3) {
            $count_start = 0;
            $count_finish = count($ked_k_v);
            while ($count_start < $count_finish) {
                if(strpos($ked_k_v[$count_start][0], $arg1) !== false && strpos($ked_k_v[$count_start][1], $arg2) !== false && strpos($ked_k_v[$count_start][2], $arg3) !== false){
                        update_post_meta($product->get_ID(), '_regular_price', (float)$ked_k_v[$count_start][3]);
                        update_post_meta($product->get_ID(), '_price', (float)$ked_k_v[$count_start][3]);
                }
                $count_start++;
            }
        }else{
            return;
        } 
    }
}
