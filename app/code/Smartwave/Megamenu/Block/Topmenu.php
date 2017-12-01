<?php
namespace Smartwave\Megamenu\Block;

class Topmenu extends \Magento\Framework\View\Element\Template
{

    protected $_categoryHelper;
    protected $_categoryFlatConfig;
    protected $_topMenu;
    protected $_categoryFactory;
    protected $_helper;
    protected $_filterProvider;
    protected $_blockFactory;
    protected $_megamenuConfig;
    protected $_storeManager;
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Smartwave\Megamenu\Helper\Data $helper,
        \Magento\Catalog\Model\Indexer\Category\Flat\State $categoryFlatState,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Theme\Block\Html\Topmenu $topMenu,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Magento\Cms\Model\BlockFactory $blockFactory
    ) {

        $this->_categoryHelper = $categoryHelper;
        $this->_categoryFlatConfig = $categoryFlatState;
        $this->_categoryFactory = $categoryFactory;
        $this->_topMenu = $topMenu;
        $this->_helper = $helper;
        $this->_filterProvider = $filterProvider;
        $this->_blockFactory = $blockFactory;
        $this->_storeManager = $context->getStoreManager();
        
        parent::__construct($context);
    }

    public function getCategoryHelper()
    {
        return $this->_categoryHelper;
    }

    public function getCategoryModel($id)
    {
        $_category = $this->_categoryFactory->create();
        $_category->load($id);
        
        return $_category;
    }
    
    public function getHtml($outermostClass = '', $childrenWrapClass = '', $limit = 0)
    {
        return $this->_topMenu->getHtml($outermostClass, $childrenWrapClass, $limit);
    }
    
    public function getStoreCategories($sorted = false, $asCollection = false, $toLoad = true)
    {
        return $this->_categoryHelper->getStoreCategories($sorted , $asCollection, $toLoad);
    }
    
    public function getChildCategories($category)
    {
        if ($this->_categoryFlatConfig->isFlatEnabled() && $category->getUseFlatResource()) {
            $subcategories = (array)$category->getChildrenNodes();
        } else {
            $subcategories = $category->getChildren();
        }
        
        return $subcategories;
    }
    
    public function getActiveChildCategories($category)
    {
        $children = [];
        if ($this->_categoryFlatConfig->isFlatEnabled() && $category->getUseFlatResource()) {
            $subcategories = (array)$category->getChildrenNodes();
        } else {
            $subcategories = $category->getChildren();
        }
        foreach($subcategories as $category) {
            if (!$category->getIsActive()) {
                continue;
            }
            $children[] = $category;
        }
        return $children;
    }
    
    public function getBlockContent($content = '') {
        if(!$this->_filterProvider)
            return $content;
        return $this->_filterProvider->getBlockFilter()->filter(trim($content));
    }
    
    public function getCustomBlockHtml($type='after') {
        $html = '';
        
        $block_ids = $this->_megamenuConfig['custom_links']['staticblock_'.$type];
        
        if (!$block_ids) return '';
        
        $block_ids = preg_replace('/\s/', '', $block_ids);
        $ids = explode(',', $block_ids);
        $store_id = $this->_storeManager->getStore()->getId();
        
        foreach($ids as $block_id) {
            $block = $this->_blockFactory->create();
            $block->setStoreId($store_id)->load($block_id);
            
            if(!$block) continue;
            
            $block_content = $block->getContent();
            
            if(!$block_content) continue;
            
            $content = $this->_filterProvider->getBlockFilter()->setStoreId($store_id)->filter($block_content);
            if(substr($content, 0, 4) == '<ul>')
                $content = substr($content, 4);
            if(substr($content, strlen($content) - 5) == '</ul>')
                $content = substr($content, 0, -5);

            $html .= $content;
        }
       
        return $html;
    }
    public function getSubmenuItemsHtml($children, $level = 1, $max_level = 0, $column_width=12, $menu_type = 'fullwidth', $columns = null)
    {
        $html = '';
        
        if(!$max_level || ($max_level && $max_level == 0) || ($max_level && $max_level > 0 && $max_level-1 >= $level)) {
            $column_class = "";
            if($level == 1 && $columns && ($menu_type == 'fullwidth' || $menu_type == 'staticwidth')) {
                $column_class = "col-sm-".$column_width." ";
                $column_class .= "mega-columns columns".$columns;
            }
            $html = '<ul class="subchildmenu '.$column_class.'">';
            foreach($children as $child) {
                $cat_model = $this->getCategoryModel($child->getId());
                
                $sw_menu_hide_item = $cat_model->getData('sw_menu_hide_item');
                
                if (!$sw_menu_hide_item) {
                    $sub_children = $this->getActiveChildCategories($child);
                    
                    $sw_menu_cat_label = $cat_model->getData('sw_menu_cat_label');
                    $sw_menu_icon_img = $cat_model->getData('sw_menu_icon_img');
                    $sw_menu_font_icon = $cat_model->getData('sw_menu_font_icon');

                    $item_class = 'level'.$level.' ';
                    if(count($sub_children) > 0)
                        $item_class .= 'parent ';
                    $html .= '<li class="ui-menu-item '.$item_class.'">';
                    if(count($sub_children) > 0) {
                        $html .= '<div class="open-children-toggle"></div>';
                    }
                    if($level == 1 && $sw_menu_icon_img) {
                        $html .= '<div class="menu-thumb-img"><a class="menu-thumb-link" href="'.$this->_categoryHelper->getCategoryUrl($child).'"><img src="' . $this->_helper->getBaseUrl().'catalog/category/' . $sw_menu_icon_img . '" alt="'.$child->getName().'"/></a></div>';
                    }
                    $html .= '<a href="'.$this->_categoryHelper->getCategoryUrl($child).'">';
                    if ($level > 1 && $sw_menu_icon_img)
                        $html .= '<img class="menu-thumb-icon" src="' . $this->_helper->getBaseUrl().'catalog/category/' . $sw_menu_icon_img . '" alt="'.$child->getName().'"/>';
                    elseif($sw_menu_font_icon)
                        $html .= '<em class="menu-thumb-icon '.$sw_menu_font_icon.'"></em>';
                    $html .= '<span>'.$child->getName();
                    if($sw_menu_cat_label)
                        $html .= '<span class="cat-label cat-label-'.$sw_menu_cat_label.'">'.$this->_megamenuConfig['cat_labels'][$sw_menu_cat_label].'</span>';
                    $html .= '</span></a>';
                    if(count($sub_children) > 0) {
                        $html .= $this->getSubmenuItemsHtml($sub_children, $level+1, $max_level, $column_width, $menu_type);
                    }
                    $html .= '</li>';
                }
            }
            $html .= '</ul>';
        }
        
        return $html;
    }
    
    public function getMegamenuHtml()
    {
        $html = '';
        
        $categories = $this->getStoreCategories(true,false,true);
        
        $this->_megamenuConfig = $this->_helper->getConfig('sw_megamenu');
        
        $max_level = $this->_megamenuConfig['general']['max_level'];
        $html .= $this->getCustomBlockHtml('before');

        $html .= $this->getCustomBlockHtml('after');
        
        return $html;
    }
}
