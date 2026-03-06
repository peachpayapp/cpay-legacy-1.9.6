<?php
/**
 * @author Woosa Team
 */

namespace Woosa\Adyen;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;
?>
<tr class="<?php echo PREFIX;?>-style">
   <td class="p-0">

      <?php do_action(PREFIX . '\category-mapping\template\top'); ?>

      <div data-<?php echo PREFIX;?>-cm-box>
         <table class="bc-c">
            <tr>
               <td class="p-0 w-50p va-t">
                  <h3 class="m-0"><?php _e('Adyen store', 'convesiopay-woocommerce') ?></h3>
               </td>
               <td class="p-0 w-50p va-t">
                  <h3 class="m-0"><?php _e('Customer country', 'convesiopay-woocommerce') ?></h3>
               </td>
               <td class="p-0 va-t">
                  <h3 class="m-0"><?php _e('Action', 'convesiopay-woocommerce') ?></h3>
               </td>
            </tr>
            <tr>
               <td colspan="3" class="pt-0 pl-0 pr-0" data-<?php echo PREFIX;?>-cm-info>
                  <?php if( 'yes' === Util::array($_GET)->get('connected') ):?>
                     <p class="ajax-response success"><?php _e('The store have been connected.', 'convesiopay-woocommerce');?></p>
                  <?php elseif( 'yes' === Util::array($_GET)->get('removed') ):?>
                     <p class="ajax-response success"><?php _e('The connected stores have been removed.', 'convesiopay-woocommerce');?></p>
                  <?php endif;?>
               </td>
            </tr>
            <tr class="bb-1">
               <td class="p-0 pb-15 w-50p va-t">
                  <?php Country_Selection::render('service', 'tree');?>
               </td>
               <td class="p-0 pb-15 w-50p va-t">
                  <?php Country_Selection::render('shop', 'tree');?>
               </td>
               <td class="p-0 pb-15 va-t">
                  <button type="button" class="button button-primary"  data-<?php echo PREFIX;?>-sm-action="connect"><?php _e('Connect', 'convesiopay-woocommerce');?></button>
               </td>
            </tr>
            <tr>
               <td class="p-0" colspan="3">
                  <table class="bc-c striped" style="width: 100%;">
                     <?php
                        $mcs1 = new Country_Selection('service', 'tree');
                        $mcs2 = new Country_Selection('shop', 'tree');

                        foreach($results as $country => $store):

                           $trail    = ltrim(trim(strip_tags($mcs1->get_trail_template($store))), '»&nbsp;');
                           $wc_trail = ltrim(trim(strip_tags($mcs2->get_trail_template($country))), '»&nbsp;');
                           ?>
                           <tr data-<?php echo PREFIX;?>-cm-term-id="<?php echo $country;?>" data-<?php echo PREFIX;?>-cm-category-id="<?php echo $store;?>">
                              <td class="w-50p"><?php echo $trail?></td>
                              <td class="w-50p"><?php echo $wc_trail;?></td>
                              <td class="ta-r">
                                 <button type="button" class="button" data-<?php echo PREFIX;?>-sm-action="remove"><?php _e('Remove', 'convesiopay-woocommerce');?></button>
                              </td>
                           </tr>

                        <?php endforeach;?>
                  </table>
               </td>
            </tr>

            <?php if($pages > 1):?>
               <tr class="bt-1">
                  <td colspan="3">
                     <ul class="pagination">
                        <?php for($page = 1; $page <= $pages; $page++):
                           $current = Util::array($_GET)->get('term_page')
                           ?>
                           <li>
                              <?php if( (empty($current) && $page == 1) || $current == $page):?>
                                 <span class="button button-small disabled"><?php echo $page;?></span>
                              <?php else:?>
                                 <a class="button button-small" href="<?php echo SETTINGS_URL.'&tab=category_mapping&term_page='.$page;?>"><?php echo $page;?></a>
                              <?php endif;?>
                           </li>
                        <?php endfor;?>
                     </ul>
                  </td>
               </tr>
            <?php endif;?>
         </table>
      </div>
   </td>
</tr>