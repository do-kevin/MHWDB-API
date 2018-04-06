<?php
	namespace App\Scraping\Scrapers\Helpers\KiranicoWeaponParser\WeaponDataInterpreters;

	use App\Scraping\Scrapers\Helpers\KiranicoWeaponParser\WeaponData;
	use App\Scraping\Scrapers\Helpers\KiranicoWeaponParser\WeaponDataInterpreterInterface;
	use App\Utility\StringUtil;
	use Symfony\Component\DomCrawler\Crawler;

	class MaterialCraftingInterpreter implements WeaponDataInterpreterInterface {
		/**
		 * {@inheritdoc}
		 */
		public function supports(Crawler $node): bool {
			return strpos(StringUtil::clean($node->text()), 'Crafting') !== false;
		}

		/**
		 * {@inheritdoc}
		 */
		public function parse(Crawler $node, WeaponData $target): void {
			$rows = $node->filter('tr');
			$materials = [];

			for ($i = 0, $ii = $rows->count(); $i < $ii; $i++) {
				$cells = $rows->eq($i)->filter('td');

				$name = StringUtil::clean($cells->eq(0)->text());
				$amount = (int)substr(StringUtil::clean($cells->eq(1)->text()), 1);

				$materials[$name] = $amount;
			}

			$target
				->setCraftable(true)
				->setCraftingMaterials($materials);
		}
	}