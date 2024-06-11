<?php
/**
 * @copyright   2013-2024 Nekrasov Vitaliy
 * @license     GNU General Public License version 2 or later
 */
namespace Joomla\Plugin\Radicalmart\Wishboxmakefirst\Extension;

use Exception;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Component\RadicalMart\Administrator\Table\ProductTable;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\Radicalmart\Wishboxmakefirst\Button\MakeFirstButton;
use function defined;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * @since 1.0.0
 */
final class Wishboxmakefirst extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Autoload the language file.
	 *
	 * @var boolean
	 *
	 * @since 1.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * @inheritDoc
	 *
	 * @return string[]
	 *
	 * @since 1.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onRadicalMartGetCustomViews'               => 'onRadicalMartGetCustomViews',
			'onRadicalMartGetAdministratorCommands'     => 'onRadicalMartGetAdministratorCommands',
			'onRadicalMartPrepareAdministratorToolbar'  => 'onRadicalMartPrepareAdministratorToolbar',
			'onRadicalMartPrepareAdministratorListItem' => 'onRadicalMartPrepareAdministratorListItem',
			'onRadicalMartPrepareProductPrice'          => 'onRadicalMartPrepareProductPrice'
		];
	}

	/**
	 * Constructor.
	 *
	 * @param   DispatcherInterface  $dispatcher  The dispatcher
	 * @param   array                $config      An optional associative array of configuration settings
	 *
	 * @since   1.0.0
	 */
	public function __construct(DispatcherInterface $dispatcher, array $config)
	{
		parent::__construct($dispatcher, $config);
	}

	/**
	 * @param   string   $context  Context
	 * @param   Toolbar  $toolbar  Tooolbar
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since        1.0.0
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function onRadicalMartPrepareAdministratorToolbar(string $context, Toolbar $toolbar): void
	{
		if ($context == 'com_radicalmart.products')
		{
			ToolbarHelper::custom(
				'products.makefirst',
				'refresh',
				'refresh',
				Text::_('PLG_RADICALMART_WISHBOXMAKEFIRST_TOOLBAR_PRODUCTS_MAKE_FIRST')
			);
		}
	}

	/**
	 * Listener for the `onRadicalMartGetAdministratorCommands` event.
	 *
	 * @param   string|null  $context   Context selector string.
	 * @param   array|null   $commands  Commands data array.
	 *
	 * @since  1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function onRadicalMartGetAdministratorCommands(?string $context = null, ?array &$commands = []): void
	{
		$this->loadCommands($context, $commands);
	}

	/**
	 * @param   array  $views  Views
	 *
	 * @throws Exception
	 *
	 * @since        1.0.0
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function onRadicalMartGetCustomViews(array $views = []): void
	{
		$app = Factory::getApplication();

		$task = $app->input->get('task');

		if ($task == 'products.makefirst')
		{
			$cid = $app->input->get('cid', []);

			$this->makeFirst($cid);
		}
	}

	/**
	 * @param   string  $context  Context
	 * @param   object  $item     Item
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function onRadicalMartPrepareAdministratorListItem(string $context, object &$item): void
	{
		if ($context != 'com_radicalmart.products')
		{
			return;
		}

		if (!isset($item->columnBeforeState))
		{
			$item->columnBeforeState = '';
		}

		$maxOrdering = $this->getMaxOrdering();

		static $i = 0;

		if ($item->ordering < $maxOrdering)
		{
			$item->columnBeforeState .= (new MakeFirstButton)->render(
				0,
				$i,
				[
					'task_prefix' => 'products.',
					'id'          => 'makeFirst-' . $item->id
				]
			);
		}

		$i++;
	}

	/**
	 * Method to add administrator commands config.
	 *
	 * @param   string|null  $context   Context selector string.
	 * @param   array        $commands  Administrator commands array.
	 *
	 * @since  1.0.0
	 */
	protected function loadCommands(?string $context = null, array &$commands = []): void
	{
		if (in_array($context, ['com_radicalmart.commands', 'com_radicalmart.products']))
		{
			$commands['radicalmart:products:makefirst'] = [
				'command' => 'radicalmart:products:makefirst',
				'text'    => 'PLG_RADICALMART_WISHBOXMAKEFIRST_COMMANDS_PRODUCTS_MAKE_FIRST',
				'method'  => [$this,  'productsMakeFirst'],
				'all' => false
			];
		}
	}

	/**
	 * @param   string  $task  Task
	 *
	 * @return array
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function productsMakeFirst(string $task): array
	{
		/** @var AdministratorApplication $app */
		$app = Factory::getApplication();

		$cid = $app->input->get('cid', []);

		$this->makeFirst($cid);

		return [$cid];
	}

	/**
	 * @param   array  $cid  Cid
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function makeFirst(array $cid): void
	{
		/** @var AdministratorApplication $app */
		$app = Factory::getApplication();

		$maxOrdering = $this->getMaxOrdering();

		foreach ($cid as $id)
		{
			/** @var ProductTable $productTable */
			$productTable = $app->bootComponent('com_radicalmart')
				->getMVCFactory()
				->createTable('product', 'Administrator');

			$productTable->load($id);

			if ($productTable->ordering < $maxOrdering)
			{
				$productTable->ordering = $maxOrdering + 1;
				$productTable->store();
			}
		}
	}

	/**
	 * @return integer
	 *
	 * @since 1.0.0
	 */
	protected function getMaxOrdering(): int
	{
		/** @var DatabaseDriver $db */
		$db = Factory::getContainer()->get(DatabaseDriver::class);

		$db->setQuery(
			$db->getQuery(true)
				->select('MAX(ordering)')
				->from('#__radicalmart_products')
		);

		return (int) $db->loadResult();
	}
}
