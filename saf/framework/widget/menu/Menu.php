<?php
namespace SAF\Framework\Widget;

use SAF\Framework\Plugin\Configurable;
use SAF\Framework\Tools\Names;
use SAF\Framework\Widget\Menu\Block;
use SAF\Framework\Widget\Menu\Item;

/**
 * A standard menu for your application
 */
class Menu implements Configurable
{

	//------------------------------------------------------- Menu configuration array keys constants
	const ALL    = ':';
	const CLEAR  = 'clear';
	const LINK   = 'link';
	const MODULE = 'module';
	const TARGET = 'target';
	const TITLE  = 'title';

	//--------------------------------------------------------------------------------------- $blocks
	/**
	 * @var Block[]
	 */
	public $blocks;

	//---------------------------------------------------------------------------------------- $title
	/**
	 * @var string
	 */
	public $title;

	//----------------------------------------------------------------------------------- $title_link
	/**
	 * link for the title
	 *
	 * @var string
	 */
	public $title_link;

	//---------------------------------------------------------------------------- $title_link_target
	/**
	 * target of the title link
	 *
	 * @var string
	 */
	public $title_link_target;

	//----------------------------------------------------------------------------------- __construct
	/**
	 * @param $configuration array
	 */
	public function __construct($configuration = [])
	{
		foreach ($configuration as $block_key => $items) {
			if ($block_key == self::TITLE) {
				foreach ($items as $item) {
					if     (substr($item, 0, 1) == SL)  $this->title_link        = $item;
					elseif (substr($item, 0, 1) == '#') $this->title_link_target = $item;
					else                                $this->title             = $item;
				}
			}
			else {
				$block = new Block();
				if (substr($block_key, 0, 1) == SL) $block->title_link = $block_key;
				else                                $block->title      = $block_key;
				foreach ($items as $item_key => $item) {
					if     ($item_key == self::MODULE) $block->module            = $item;
					elseif ($item_key == self::TITLE)  $block->title             = $item;
					elseif ($item_key == self::LINK)   $block->title_link        = $item;
					elseif ($item_key == self::TARGET) $block->title_link_target = $item;
					else {
						$menu_item = new Item();
						$menu_item->link = $item_key;
						if (is_array($item)) {
							foreach ($item as $property_key => $property) {
								if (is_numeric($property_key)) {
									if     (substr($property, 0, 1) == SL)  $menu_item->link        = $property;
									elseif (substr($property, 0, 1) == '#') $menu_item->link_target = $property;
									else                                    $menu_item->caption     = $property;
								}
							}
						}
						else {
							$menu_item->caption = $item;
						}
						$block->items[] = $menu_item;
					}
				}
				if (!isset($block->module)) {
					$block->module = Names::displayToProperty($block_key);
				}
				$this->blocks[] = $block;
			}
		}
	}

}
