<?php

require('includes/application_top.php');

//init vars
global $db;
$i = 0;
$j = 0;
$cats_to_update = array();
$show_name_numbers = true;
$what_went_down = "";
$action = (isset($_GET['action']) ? $_GET['action'] : '');
define('BUTTON_IMAGE_SUBMIT', 'button_submit.gif');
define('TABLE_HEADING_OPT_NAME', 'Option Name');
define('TABLE_HEADING_OPT_VALUE', 'Option Value');

//process $_POST DATA SECTION
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['att_auto_to_cat']))){
        echo 'doing something in $_POST';
        $insert_sql = "INSERT INTO `products_attributes_auto` 
                        (`products_attribute_id`, 
                        `att_auto_to_cat`, 
                        `auto_cat_options_id`, 
                        `auto_cat_options_values_id`, 
                        `att_auto_comments`) 
                    VALUES (NULL, " .
                            $_POST['att_auto_to_cat'] .", ".
                            $_POST['options_id'] .", ".
                            $_POST['values_id'][0] .", '".
                            $_POST['comment'] ."'
                        );";                        
                        //echo $insert_sql;
                        /*echo '<pre>';
                        //print_r($_POST);
                        echo '</pre>';*/
                        $db->Execute($insert_sql);
}                        
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['Delete']))){
                        if(!empty($_POST['Delete'])){
                            $delete_sql = "DELETE FROM `products_attributes_auto` 
                        WHERE `products_attributes_auto`.products_attribute_id = " . $_POST['Delete'] . ";";
        echo 'Did ... ' . $delete_sql . '<br />Note: this does not remove attributes that were applied by the original mapping';
        $db->Execute($delete_sql);
    }    
}

//Find whats in database to build out as reference table these will get "mapped" and have attributes automatically applied
$auto_attribs_sql = 'SELECT * FROM products_attributes_auto;';
$auto_attribs_results = $db->Execute($auto_attribs_sql);

foreach($auto_attribs_results as $auto_attribs_result){
    $i++;
    /*echo 'We would like to make all products with category id of ';
    echo $auto_attribs_result['att_auto_to_cat'];
    echo ' to have options id of ' . $auto_attribs_result['auto_cat_options_id'];
    echo ' with a options value of ' . $auto_attribs_result['auto_cat_options_values_id'];
    echo '<br />';
    echo '<br />';*/
    $cats_to_update[$i]['att_auto_to_cat'] = $auto_attribs_result['att_auto_to_cat'];
    $cats_to_update[$i]['auto_cat_options_id'] = $auto_attribs_result['auto_cat_options_id'];
    $cats_to_update[$i]['auto_cat_options_values_id'] = $auto_attribs_result['auto_cat_options_values_id'];
}
/*echo 'cats_to_update is <pre>';
print_r($cats_to_update);
echo '</pre>';*/

//pulls mappings and implements application of attributes accordingly
foreach($cats_to_update as $cat){
    //find in-stock/status=1 stuff that does not have appropriate attributes according to mapping and category
    $missingAttributesSql = 'SELECT 
                                p.`products_id`, p.`products_status`, p2c.*
                            FROM
                                `products` AS p
                                    INNER JOIN
                                `products_to_categories` AS p2c 
                                        ON (p.`products_id` = p2c.`products_id`
                                            AND p.`products_status` = 1
                                            AND p2c.`categories_id` = ' . $cat['att_auto_to_cat'] . ')
                                    LEFT JOIN
                                `products_attributes` AS pa 
                                        ON (pa.`products_id` = p.`products_id`
                                            AND pa.`options_values_id` = ' . $cat['auto_cat_options_values_id'] . ')
                                    WHERE
                                        pa.products_id IS NULL';

            $missingAttributesResults = $db->Execute($missingAttributesSql);

            //foreach of the products missing attributes apply the attributes
            foreach($missingAttributesResults as $missingAttributesResult){
                $sql_data_array = array('products_attributes_id' => NULL,
                                        'products_id' => $missingAttributesResult['products_id'],
                                        'options_id' => $cat['auto_cat_options_id'], //22 means "width"
                                        'options_values_id' => $cat['auto_cat_options_values_id'], //91 = 8.0in  111 = 8.25in
                                        'options_values_price' => '0.0000',
                                        'price_prefix' => '+',
                                        'products_options_sort_order' => '82',
                                        'product_attribute_is_free' => '1',
                                        'products_attributes_weight' => '0',                            
                                        'products_attributes_weight_prefix' => '+',
                                        'attributes_display_only' => '0',
                                        'attributes_default' => '0',
                                        'attributes_discounted' => '1',
                                        'attributes_image' => NULL,
                                        'attributes_price_base_included' => '1',
                                        'attributes_price_onetime' => '0.0000',
                                        'attributes_price_factor' => '0.0000',
                                        'attributes_price_factor_offset' => '0.0000',
                                        'attributes_price_factor_onetime' => '0.0000',
                                        'attributes_price_factor_onetime_offset' => '0.0000',
                                        'attributes_qty_prices' => '',
                                        'attributes_qty_prices_onetime' => '',
                                        'attributes_price_words' => '0.0000',
                                        'attributes_price_words_free' => '0',
                                        'attributes_price_letters' => '0.0000',
                                        'attributes_price_letters_free' => '0',
                                        'attributes_required' => '0');
                $what_went_down .= $missingAttributesResult['products_id'] . ', '; //build out feedback text
                zen_db_perform(TABLE_PRODUCTS_ATTRIBUTES, $sql_data_array);
            }
}   
/*echo '<pre>';
print_r($what_went_down);*/
if(!empty($what_went_down)){
    echo 'Updated these product id\'s ... ' . $what_went_down;
}
?>

<!doctype html>
<html <?php echo HTML_PARAMS; ?>>
  <head>
    <meta charset="<?php echo CHARSET; ?>">
    <title><?php echo 'Auto Attributes'; ?></title>
    <link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
    <!--<link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">-->
    <!--<script src="includes/menu.js"></script>
    <script src="includes/general.js"></script>-->
  </head>
  <body onload="init();">
  <?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<a style="text-align:center; font-size:30px; display:block;" href="auto_attrib.php">Auto Attrib</a>

<?php
$autoCatTableSql = 'SELECT pa.*, cd.categories_id, cd.categories_name, po.products_options_id, po.products_options_name, pov.products_options_values_id, pov.products_options_values_name 
                    FROM `products_attributes_auto` as PA 
                    JOIN `categories_description` as cd 
                        on pa.att_auto_to_cat = cd.categories_id 
                    JOIN `products_options` as po 
                        on po.products_options_id = pa.auto_cat_options_id 
                    JOIN `products_options_values` as pov 
                        on pa.auto_cat_options_values_id = pov.products_options_values_id
                    ORDER BY cd.categories_name;';
$autoCatTableSqlResults = $db->Execute($autoCatTableSql);

/*echo 'autoCatTableSqlResults is <pre>';
print_r($autoCatTableSqlResults);
echo '</pre>';*/
if ($autoCatTableSqlResults->RecordCount() > 0) {
        ?>
    <h1>Things in your auto-mapping-categories-to-attributes Table:</h1>
    <table style="border: 1px black">
        <th>att_auto_to_cat</th>
        <th>categories_name</th>
        <th>auto_cat_options_id</th>
        <th>products_options_name</th>
        <th>auto_cat_options_values_id</th>
        <th>products_options_values_name</th>
        <th>att_auto_comments</th>
        <th>delete?</th>
    <tbody>

    <?php
        //while (!$autoCatTableSqlResults->EOF) {		
    echo zen_draw_form('Delete', 'auto_attrib.php');
        foreach($autoCatTableSqlResults as $autoCatTableSqlResult){
            //print_r($autoCatTableSqlResult);
            echo '<TR>';
                echo '<TD>';
                echo $autoCatTableSqlResult['att_auto_to_cat'];
                echo '</TD>';
                echo '<TD>';
                echo $autoCatTableSqlResult['categories_name'];
                echo '</TD>';
                echo '<TD>';
                echo $autoCatTableSqlResult['auto_cat_options_id'];
                echo '</TD>';
                echo '<TD>';
                echo $autoCatTableSqlResult['products_options_name'];
                echo '</TD>';
                echo '<TD>';
                echo $autoCatTableSqlResult['auto_cat_options_values_id'];
                echo '</TD>';            
                echo '<TD>';
                echo $autoCatTableSqlResult['products_options_values_name'];
                echo '</TD>';
                echo '<TD>';
                echo $autoCatTableSqlResult['att_auto_comments'];
                echo '</TD>';
                echo '<TD>';
                //echo zen_image_submit('button_delete.gif', 'Delete', 'value='.$autoCatTableSqlResult['products_attribute_id']);
                echo "<button name='Delete' value=".$autoCatTableSqlResult['products_attribute_id'].">Delete</button>";
                echo '</TD>';
            echo '</TR>';
        }
}
    ?>
    </form>
    </tbody>
</table>
<style>
    td, th { border: 1px solid }    
</style>
<?php
    function translate_type_to_name($opt_type) {
        global $products_options_types_list;
        return $products_options_types_list[$opt_type];
    }
    function zen_js_option_values_list($selectedName, $fieldName) {
        global $db, $show_value_numbers;
        $attributes_sql = "SELECT povpo.products_options_id, povpo.products_options_values_id,
                              po.products_options_name, po.products_options_sort_order,
                              pov.products_options_values_name, pov.products_options_values_sort_order
                            FROM " . TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS . " povpo,
                                    " . TABLE_PRODUCTS_OPTIONS . " po,
                                    " . TABLE_PRODUCTS_OPTIONS_VALUES . " pov
                            WHERE povpo.products_options_id = po.products_options_id
                            AND povpo.products_options_values_id = pov.products_options_values_id
                            AND pov.language_id = po.language_id
                            AND po.language_id = " . (int)$_SESSION['languages_id'] . "
                            ORDER BY po.products_options_id, po.products_options_name, pov.products_options_values_name";

    $attributes = $db->Execute($attributes_sql);
    $counter = 1;
    $val_count = 0;
    $value_string = '  // Build conditional Option Values Lists' . "\n";
    $last_option_processed = null;
    foreach ($attributes as $attribute) {
      $products_options_values_name = str_replace('-', '\-', $attribute['products_options_values_name']);
      $products_options_values_name = str_replace('(', '\(', $products_options_values_name);
      $products_options_values_name = str_replace(')', '\)', $products_options_values_name);
      $products_options_values_name = str_replace('"', '\"', $products_options_values_name);
      $products_options_values_name = str_replace('&quot;', '\"', $products_options_values_name);
      $products_options_values_name = str_replace('&frac12;', '1/2', $products_options_values_name);
  
      if ($counter == 1) {
        $value_string .= '  if (' . $selectedName . ' == "' . $attribute['products_options_id'] . '") {' . "\n";
      } elseif ($last_option_processed != $attribute['products_options_id']) {
        $value_string .= '  } else if (' . $selectedName . ' == "' . $attribute['products_options_id'] . '") {' . "\n";
        $val_count = 0;
      }
  
      $value_string .= '    ' . $fieldName . '.options[' . $val_count . '] = new Option("' . $products_options_values_name . ($attribute['products_options_values_id'] == 0 ? '/UPLOAD FILE' : '') . ($show_value_numbers ? ' [ #' . $attribute['products_options_values_id'] . ' ] ' : '') . '", "' . $attribute['products_options_values_id'] . '");' . "\n";
  
      $last_option_processed = $attribute['products_options_id'];
      $val_count++;
      $counter++;
    }
    if ($counter > 1) {
      $value_string .= '  }' . "\n";
    }
    return $value_string;
  }

$options_values = $db->Execute("SELECT products_options_id, products_options_name, products_options_type
FROM " . TABLE_PRODUCTS_OPTIONS . "
WHERE language_id = " . (int)$_SESSION['languages_id'] . "
ORDER BY products_options_name");

/*echo '<pre>';
print_r($options_values);
echo '</pre>';*/

$optionsDropDownArray = [];
foreach ($options_values as $options_value) {
    $optionsDropDownArray[] = [
        'id' => $options_value['products_options_id'],
        'text' => $options_value['products_options_name'] . '&nbsp;&nbsp;&nbsp;[' . translate_type_to_name($options_value['products_options_type']) . ']' . ($show_name_numbers ? ' &nbsp; [ #' . $options_value['products_options_id'] . ' ] ' : '' )
    ];
}

/*echo '<pre>';
//print_r($optionsDropDownArray);
echo '</pre>';*/
?>
<br />
<br />
<br />
<h1>To associate attributes to categories create the mappings using this submit:</h1>
<table>
<tbody>
<td>
    <?php echo zen_draw_form('auto_attrib_form_name','auto_attrib.php'); ?>
</td>
<td><b>Pick a category to map:</b>
    <?php echo zen_draw_pull_down_menu('att_auto_to_cat', zen_get_category_tree(), $current_category_id, 'class="form-control"'); ?>
</td>
<td>
    <div class="col-xs-6 col-sm-6 col-md-6 col-lg-5">
        <?php echo zen_draw_label(TABLE_HEADING_OPT_NAME, 'options_id'); ?>
        <?php echo zen_draw_pull_down_menu('options_id', $optionsDropDownArray, '', 'id="OptionName" size="' . ($action != 'delete_attribute' ? "15" : "1") . '" onchange="update_option(this.form)" class="form-control"'); ?>
    </div>
    <div class="col-xs-6 col-sm-6 col-md-6 col-lg-5">
        <?php echo zen_draw_label(TABLE_HEADING_OPT_VALUE, 'values_id', 'class="control-label"'); ?>
    <select name="values_id[]" id="OptionValue" class="form-control" multiple="multiple" <?php echo 'size="' . ($action != 'delete_attribute' ? "15" : "1") . '"'; ?>>
        <option selected>&lt;-- Please select an Option Name from the list ... </option>
    </select>
    </div>
</td>
<td>
    <div>
        <?php echo '<b>Comments:</b> ' .  zen_draw_input_field('comment',''); ?>
    </div>                  
</td>
</tbody>
</table>

<?php echo zen_image_submit(BUTTON_IMAGE_SUBMIT, 'Submit Alt'); ?>
    <script type="text/javascript">
      function update_option(theForm) {
          // if nothing to do, abort
          console.log(theForm);
          if (!theForm || !theForm.elements["options_id"] || !theForm.elements["values_id[]"])
              return;
          if (!theForm.options_id.options[theForm.options_id.selectedIndex])
              return;

          // enable hourglass
          document.body.style.cursor = "wait";

          // set initial values
          var SelectedOption = theForm.options_id.options[theForm.options_id.selectedIndex].value;
          var theField = document.getElementById("OptionValue");

          // reset the array of pulldown options so it can be repopulated
          var Opts = theField.options.length;
          while (Opts > 0) {
              Opts = Opts - 1;
              theField.options[Opts] = null;
          }

<?php echo zen_js_option_values_list('SelectedOption', 'theField'); ?>

          // turn off hourglass
          document.body.style.cursor = "default";
      }
    </script>
  </body>
</html>