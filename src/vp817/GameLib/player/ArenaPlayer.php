<?php

/**
 *   .oooooo.                                          ooooo         o8o   .o8
 *  d8P'  `Y8b                                         `888'         `"'  "888
 * 888            .oooo.   ooo. .oo.  .oo.    .ooooo.   888         oooo   888oooo.
 * 888           `P  )88b  `888P"Y88bP"Y88b  d88' `88b  888         `888   d88' `88b
 * 888     ooooo  .oP"888   888   888   888  888ooo888  888          888   888   888
 * `88.    .88'  d8(  888   888   888   888  888    .o  888       o  888   888   888
 *  `Y8bood8P'   `Y888""8o o888o o888o o888o `Y8bod8P' o888ooooood8 o888o  `Y8bod8P'
 * 
 * @author vp817, Laith98Dev
 * 
 * Copyright (C) 2023  vp817
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace vp817\GameLib\player;

use pocketmine\entity\effect\EffectManager;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use vp817\GameLib\utils\Team;

final class ArenaPlayer
{

    private array $savedCells = [];
    private Player $cells;

    private ?Team $team = null;
    private string $displayName;
    private string $nameTag;
    private PlayerInventory $inventory;
    private ArmorInventory $armorInventory;
    private EffectManager $effectManager;
    private float $health;
    private int $maxHealth;
    private float $food;
    private GameMode $gamemode;

    /**
     * @param Player $player
     */
    public function __construct(Player $player)
    {
        $this->cells = $player;

        $this->displayName = $player->getDisplayName();
        $this->nameTag = $player->getName();
        $this->inventory = clone $player->getInventory();
        $this->armorInventory = clone $player->getArmorInventory();
        $this->effectManager = clone $player->getEffects();
        $this->health = $player->getHealth();
        $this->maxHealth = $player->getMaxHealth();
        $this->food = $player->getHungerManager()->getFood();
        $this->gamemode = $player->getGamemode();

        $this->savedCells = [
            "displayName" => $this->displayName,
            "nameTag" => $this->nameTag,
            "inventory" => $this->inventory->getContents(),
            "armorInventory" => $this->armorInventory->getContents(),
            "effects" => $this->effectManager->all(),
            "health" => $this->health,
            "maxHealth" => $this->maxHealth,
            "food" => $this->food,
            "gamemode" => $this->gamemode
        ];
    }

    /**
     * @param Team|null $team
     * @return void
     */
    public function setTeam(?Team $team): void
    {
        $this->team = $team;
    }

    /**
     * @param string $value
     * @return void
     */
    public function setDisplayName(string $value): void
    {
        $this->displayName = $value;
    }

    /**
     * @param string $value
     * @return void
     */
    public function setNameTag(string $value): void
    {
        $this->nameTag = $value;
    }

    /**
     * @param float $value
     * @return void
     */
    public function setHealth(float $value): void
    {
        $this->health = $value;
    }

    /**
     * @param int $value
     * @return void
     */
    public function setMaxHealth(int $value): void
    {
        $this->maxHealth = $value;
    }

    /**
     * @param float $value
     * @return void
     */
    public function setFood(float $value): void
    {
        $this->food = $value;
    }

    /**
     * @param GameMode $value
     * @return void
     */
    public function setGamemode(GameMode $value): void
    {
        $this->gamemode = $value;
    }

    /**
     * @return null|Team
     */
    public function getTeam(): ?Team
    {
        return $this->team;
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * @return string
     */
    public function getNameTag(): string
    {
        return $this->nameTag;
    }

    /**
     * @return PlayerInventory
     */
    public function getInventory(): PlayerInventory
    {
        return $this->inventory;
    }

    /**
     * @return ArmorInventory
     */
    public function getArmorInventory(): ArmorInventory
    {
        return $this->armorInventory;
    }

    /**
     * @return EffectManager
     */
    public function getEffectManager(): EffectManager
    {
        return $this->effectManager;
    }

    /**
     * @return float
     */
    public function getHealth(): float
    {
        return $this->health;
    }

    /**
     * @return int
     */
    public function getMaxHealth(): int
    {
        return $this->maxHealth;
    }

    /**
     * @return float
     */
    public function getFood(): float
    {
        return $this->food;
    }

    /**
     * @return GameMode
     */
    public function getGamemode(): GameMode
    {
        return $this->gamemode;
    }

    /**
     * @return void
     */
    public function initBasic(): void
    {
        $this->getInventory()->clearAll();
        $this->getArmorInventory()->clearAll();
        $this->getEffectManager()->clear();

        $effects = $this->getEffectManager()->all();
        if (!empty($effects)) {
            foreach ($effects as $effect) {
                $this->getEffectManager()->add(effect: $effect);
            }
        }

        $this->setHealth(value: $this->getHealth());
        $this->setMaxHealth(value: $this->getMaxHealth());
        $this->setFood(value: $this->getFood());
        $this->setGamemode(value: GameMode::ADVENTURE());
    }

    /**
     * @return void
     */
    public function deinitBasic(): void
    {
        $this->getInventory()->setContents(items: $this->savedCells["inventory"]);
        $this->getArmorInventory()->setContents(items: $this->savedCells["armorInventory"]);
        $this->getEffectManager()->clear();

        $effects = $this->savedCells["effects"];
        if (!empty($effects)) {
            foreach ($effects as $effect) {
                $this->getEffectManager()->add(effect: $effect);
            }
        }

        $this->setHealth(value: $this->savedCells["health"]);
        $this->setMaxHealth(value: $this->savedCells["maxHealth"]);
        $this->setFood(value: $this->savedCells["food"]);
        $this->setGamemode(value: $this->savedCells["gamemode"]);
    }

    /**
     * @return Player
     */
    public function getCells(): Player
    {
        return $this->cells;
    }

    /**
     * @return void
     */
    public function setAll(bool $unsetSavedCells = false): void
    {
        $this->getCells()->getInventory()->setContents(items: $this->getInventory()->getContents());
        $this->getCells()->getArmorInventory()->setContents(items: $this->getArmorInventory()->getContents());
        $this->getCells()->getEffects()->clear();

        $effects = $this->getEffectManager()->all();
        foreach ($effects as $effect) {
            $this->getCells()->getEffects()->add(effect: $effect);
        }

        $this->getCells()->setHealth(amount: $this->getHealth());
        $this->getCells()->setMaxHealth(amount: $this->getMaxHealth());
        $this->getCells()->getHungerManager()->setFood(new: $this->getFood());
        $this->getCells()->setGamemode(gm: $this->getGamemode());

        if ($unsetSavedCells) {
            unset($this->savedCells);
        }
    }
}
