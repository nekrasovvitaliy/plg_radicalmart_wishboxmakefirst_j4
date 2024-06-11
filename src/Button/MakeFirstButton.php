<?php
/**
 * @copyright   2013-2024 Nekrasov Vitaliy
 * @license     GNU General Public License version 2 or later
 */
namespace Joomla\Plugin\Radicalmart\Wishboxmakefirst\Button;

use Exception;
use InvalidArgumentException;
use Joomla\CMS\Button\ActionButton;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Utilities\ArrayHelper;
use function defined;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * @since  1.0.0
 */
class MakeFirstButton extends ActionButton
{
	/**
	 * The layout path to render.
	 *
	 * @var  string
	 *
	 * @since  4.0.0
	 */
	protected $layout = 'joomla.button.wishboxmakefirst-button';

    /**
     * Configure this object.
     *
     * @return  void
     *
     * @since  1.0.0
     *
     * @noinspection PhpMissingReturnTypeInspection
     */
    protected function preprocess()
    {
	    $this->addState(
		    0,
		    'makefirst',
		    'icon-publish',
		    Text::_('PLG_RADICALMART_WISHBOXMAKEFIRST_TOOLBAR_PRODUCTS_MAKE_FIRST'),
		    [
				'tip_title' => Text::_('')
		    ]
	    );
    }

	/**
	 * Render action button by item value.
	 *
	 * @param   integer|null  $value    Current value of this item.
	 * @param   integer|null  $row      The row number of this item.
	 * @param   array         $options  The options to override group options.
	 *
	 * @return  string  Rendered HTML.
	 *
	 * @throws  InvalidArgumentException|Exception
	 *
	 * @since   1.0.0
	 */
	public function render(?int $value = null, ?int $row = null, array $options = []): string
	{
		/** @var WebAssetManager $assets */
		$assets = Factory::getApplication()->getDocument()->getWebAssetManager();
		$assets->addInlineStyle(
			'.icon-wishboxmakefirst {background:url(' . URI::root() . 'media/plg_radicalmart_wishboxmakefirst/images/makefirst.svg) no-repeat; border: none !important}'
		);
		$assets->addInlineStyle(
			'.icon-wishboxmakefirst:hover {background:url(' . URI::root() . 'media/plg_radicalmart_wishboxmakefirst/images/makefirst_hover.svg) no-repeat; border: none !important}'
		);

		$data = $this->getState($value) ?? $this->unknownState;

		$data = ArrayHelper::mergeRecursive(
			$this->unknownState,
			$data,
			[
				'options' => $this->options->toArray(),
			],
			[
				'options' => $options,
			]
		);

		$data['row']  = $row;
		$data['icon'] = $this->fetchIconClass($data['icon']);

		return LayoutHelper::render(
			$this->layout,
			$data,
			JPATH_SITE . '/plugins/radicalmart/wishboxmakefirst/layouts'
		);
	}
}
