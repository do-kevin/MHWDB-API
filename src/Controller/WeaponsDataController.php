<?php
	namespace App\Controller;

	use App\Entity\CraftingMaterialCost;
	use App\Entity\Slot;
	use App\Entity\Weapon;
	use App\Entity\WeaponElement;
	use App\Entity\WeaponSharpness;
	use App\Game\WeaponType;
	use App\QueryDocument\Projection;
	use DaybreakStudios\Utility\DoctrineEntities\EntityInterface;
	use Doctrine\Common\Collections\Collection;
	use Symfony\Component\HttpFoundation\Request;
	use Symfony\Component\HttpFoundation\Response;
	use Symfony\Component\Routing\Annotation\Route;

	class WeaponsDataController extends AbstractDataController {
		/**
		 * WeaponsDataController constructor.
		 */
		public function __construct() {
			parent::__construct(Weapon::class);
		}

		/**
		 * @Route(path="/weapons", methods={"GET"}, name="weapons.list")
		 *
		 * @param Request $request
		 *
		 * @return Response
		 */
		public function list(Request $request): Response {
			return parent::list($request);
		}

		/**
		 * @Route(path="/weapons/{weapon<\d+>}", methods={"GET"}, name="weapons.read")
		 *
		 * @param Weapon $weapon
		 *
		 * @return Response
		 */
		public function read(Weapon $weapon): Response {
			return $this->respond($weapon);
		}

		/**
		 * @param EntityInterface|Weapon|null $entity
		 * @param Projection                  $projection
		 *
		 * @return array|null
		 */
		protected function normalizeOne(?EntityInterface $entity, Projection $projection): ?array {
			if (!$entity)
				return null;

			$output = [
				'id' => $entity->getId(),
				'name' => $entity->getName(),
				'type' => $entity->getType(),
				'rarity' => $entity->getRarity(),
				'attack' => [
					'display' => $entity->getAttack()->getDisplay(),
					'raw' => $entity->getAttack()->getRaw(),
				],
				// default to \stdClass to fix an empty array being returned instead of an empty object
				'attributes' => $entity->getAttributes() ?: new \stdClass(),
			];

			if (WeaponType::isMelee($entity->getType()) && $projection->isAllowed('durability')) {
				$durability = $entity->getDurability();

				$output['durability'] = array_map(function(WeaponSharpness $sharpness): array {
					return [
						'red' => $sharpness->getRed(),
						'orange' => $sharpness->getOrange(),
						'yellow' => $sharpness->getYellow(),
						'green' => $sharpness->getGreen(),
						'blue' => $sharpness->getBlue(),
						'white' => $sharpness->getWhite(),
					];
				}, $durability->toArray());
			}
			// endregion

			// region Slots Fields
			if ($projection->isAllowed('slots')) {
				$output['slots'] = array_map(function(Slot $slot): array {
					return [
						'rank' => $slot->getRank(),
					];
				}, $entity->getSlots()->toArray());
			}
			// endregion

			// region Elements Fields
			if ($projection->isAllowed('elements')) {
				$output['elements'] = array_map(function(WeaponElement $element): array {
					return [
						'type' => $element->getType(),
						'damage' => $element->getDamage(),
						'hidden' => $element->isHidden(),
					];
				}, $entity->getElements()->toArray());
			}
			// endregion

			// region Crafting Fields
			if ($projection->isAllowed('crafting')) {
				$crafting = $entity->getCrafting();

				if ($crafting) {
					/**
					 * @param string                            $type
					 * @param Collection|CraftingMaterialCost[] $costs
					 *
					 * @return array
					 */
					$transformer = function(string $type, Collection $costs) use ($projection): array {
						return array_map(function(CraftingMaterialCost $cost) use ($projection, $type): array {
							$output = [
								'quantity' => $cost->getQuantity(),
							];

							// region Item Fields
							if ($projection->isAllowed(sprintf('crafting.%s.item', $type))) {
								$item = $cost->getItem();

								$output['item'] = [
									'id' => $item->getId(),
									'name' => $item->getName(),
									'description' => $item->getDescription(),
									'rarity' => $item->getRarity(),
									'carryLimit' => $item->getCarryLimit(),
									'value' => $item->getValue(),
								];
							}

							// endregion

							return $output;
						}, $costs->toArray());
					};

					$output['crafting'] = [
						'craftable' => $crafting->isCraftable(),
					];

					// region Previous Weapon Fields
					if ($projection->isAllowed('crafting.previous')) {
						$previous = $crafting->getPrevious();

						$output['crafting']['previous'] = $previous ? $previous->getId() : null;
					}
					// endregion

					// region Branches Fields
					if ($projection->isAllowed('crafting.branches')) {
						$output['crafting']['branches'] = array_map(function(Weapon $branch): int {
							return $branch->getId();
						}, $crafting->getBranches()->toArray());
					}
					// endregion

					// region Crafting Materials Fields
					if ($projection->isAllowed('crafting.craftingMaterials')) {
						$output['crafting']['craftingMaterials'] = call_user_func($transformer, 'craftingMaterials',
							$crafting->getCraftingMaterials());
					}
					// endregion

					// region Upgrade Materials Fields
					if ($projection->isAllowed('crafting.upgradeMaterials')) {
						$output['crafting']['upgradeMaterials'] = call_user_func($transformer, 'upgradeMaterials',
							$crafting->getUpgradeMaterials());
					}
					// endregion
				} else
					$output['crafting'] = null;
			}
			// endregion

			// region Assets Fields
			if ($projection->isAllowed('assets')) {
				$assets = $entity->getAssets();

				if ($assets) {
					$output['assets'] = [];

					// region Icon Fields
					if ($projection->isAllowed('assets.icon')) {
						$icon = $assets->getIcon();

						$output['assets']['icon'] = $icon ? $icon->getUri() : null;
					}
					// endregion

					// region Image Fields
					if ($projection->isAllowed('assets.image')) {
						$image = $assets->getImage();

						$output['assets']['image'] = $image ? $image->getUri() : null;
					}
					// endregion
				} else
					$output['assets'] = null;
			}

			// endregion

			return $output;
		}
	}