<?php
	namespace App\Entity;

	use DaybreakStudios\Utility\DoctrineEntities\EntityInterface;
	use Doctrine\Common\Collections\ArrayCollection;
	use Doctrine\Common\Collections\Collection;
	use Doctrine\Common\Collections\Selectable;
	use Doctrine\ORM\Mapping as ORM;

	/**
	 * @ORM\Entity()
	 * @ORM\Table(name="monster_rewards")
	 *
	 * @package App\Entity
	 */
	class MonsterReward implements EntityInterface {
		use EntityTrait;

		/**
		 * @ORM\ManyToOne(targetEntity="App\Entity\Monster", inversedBy="rewards")
		 * @ORM\JoinColumn(nullable=false)
		 *
		 * @var Monster
		 */
		protected $monster;

		/**
		 * @ORM\ManyToOne(targetEntity="App\Entity\Item")
		 * @ORM\JoinColumn(nullable=false)
		 *
		 * @var Item
		 */
		protected $item;

		/**
		 * @ORM\ManyToMany(targetEntity="App\Entity\RewardCondition")
		 * @ORM\JoinTable(name="monster_reward_conditions")
		 *
		 * @var Collection|Selectable|RewardCondition[]
		 */
		protected $conditions;

		/**
		 * MonsterReward constructor.
		 *
		 * @param Monster $monster
		 * @param Item    $item
		 */
		public function __construct(Monster $monster, Item $item) {
			$this->monster = $monster;
			$this->item = $item;

			$this->conditions = new ArrayCollection();
		}

		/**
		 * @return Monster
		 */
		public function getMonster(): Monster {
			return $this->monster;
		}

		/**
		 * @return Item
		 */
		public function getItem(): Item {
			return $this->item;
		}

		/**
		 * @return RewardCondition[]|Collection|Selectable
		 */
		public function getConditions() {
			return $this->conditions;
		}
	}