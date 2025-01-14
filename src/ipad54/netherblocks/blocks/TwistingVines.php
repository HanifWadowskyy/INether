<?php

namespace ipad54\netherblocks\blocks;

use ipad54\netherblocks\utils\CustomIds;
use pocketmine\block\Air;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\Transparent;
use pocketmine\block\utils\BlockDataSerializer;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\item\Fertilizer;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\item\Item;

class TwistingVines extends Transparent{ //credits: https://github.com/cladevs/VanillaX

    protected int $age = 0;


    public function onInteract(Item $item, int $face, Vector3 $clickVector, ?Player $player = null): bool{
        if($item instanceof Fertilizer){
            $item->pop();
            $height = 0;

            for($y = 1; $y < $this->position->getWorld()->getMaxY(); $y++){
                $block = $this->position->getWorld()->getBlock($this->position->add(0, $y, 0));

                if(!$block instanceof TwistingVines){
                    continue;
                }
                $height++;
            }
            $lastBlock = $this->position->getWorld()->getBlock($this->position->add(0, $height, 0));

            if($lastBlock instanceof TwistingVines){
                $lastAge = $lastBlock->getAge();
                $size = mt_rand(1, 6);

                for($i = 1; $i <= $size; $i++){
                    $b = $this->position->getWorld()->getBlock($this->position->add(0, $height + $i, 0));

                    if($b instanceof Air && $b->position->getY() < $b->position->getWorld()->getMaxY()){
                        if($lastAge > 15){
                            $lastAge = 15;
                        }else{
                            $lastAge++;
                        }
                        $this->position->getWorld()->setBlock($b->position, BlockFactory::getInstance()->get(CustomIds::TWISTING_VINES_BLOCK, $lastAge > 15 ? 15 : $lastAge));
                    }else{
                        break;
                    }
                }
            }
            return true;
        }
        return false;
    }

    public function onBreak(Item $item, ?Player $player = null): bool{
        $parent = parent::onBreak($item, $player);

        for($y = 1; $y < $this->position->getWorld()->getMaxY(); $y++){
            $block = $this->position->getWorld()->getBlock($this->position->add(0, $y, 0));

            if(!$block instanceof TwistingVines){
                break;
            }
            $this->position->getWorld()->useBreakOn($block->position);
        }
        return $parent;
    }

    public function onNearbyBlockChange(): void{
        $block = $this->getSide(Facing::DOWN);

        if($block instanceof Air){
            $this->position->getWorld()->useBreakOn($this->position);
        }
    }

    public function onRandomTick(): void{
        if($this->age !== 15){
            if($this->position->y === ($this->position->getWorld()->getMaxY() - 1)){
                $this->age = 15;
                return;
            }
            $b = $this->position->getWorld()->getBlockAt($this->position->x, $this->position->y + 1, $this->position->z);

            if($b->getId() === BlockLegacyIds::AIR){
                $newAge = $this->age + 1;

                $ev = new BlockGrowEvent($b, BlockFactory::getInstance()->get(CustomIds::TWISTING_VINES_BLOCK, $newAge > 15 ? 15 : $newAge));
                $ev->call();
                if($ev->isCancelled()){
                    return;
                }
                $this->position->getWorld()->setBlock($b->position, $ev->getNewState());
            }
        }
    }

    public function ticksRandomly(): bool{
        return true;
    }

    public function getDrops(Item $item): array{
        $failed = true;
        $chance = 33;
        if(mt_rand(0, 100) >= $chance){
            $failed = false;
        }
        if($failed){
            return [];
        }
        return [$this->asItem()];
    }

    public function getAge(): int{
        return $this->age;
    }

    public function getStateBitmask(): int{
        return 0b1111;
    }

    protected function writeStateToMeta(): int{
        return $this->age;
    }

    public function readStateFromData(int $id, int $stateMeta): void{
        $this->age = BlockDataSerializer::readBoundedInt("age", $stateMeta, 0, 15);
    }
}