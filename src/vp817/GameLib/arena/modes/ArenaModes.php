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

namespace vp817\GameLib\arena\modes;

use pocketmine\utils\CloningRegistryTrait;
use vp817\GameLib\arena\modes\list\DuoMode;
use vp817\GameLib\arena\modes\list\PracticeMode;
use vp817\GameLib\arena\modes\list\SoloMode;
use vp817\GameLib\arena\modes\list\SquadMode;
use vp817\GameLib\arena\modes\list\TrioMode;

/**
 * @method static SoloMode SOLO()
 * @method static DuoMode DUO()
 * @method static TrioMode TRIO()
 * @method static SquadMode SQUAD()
 * @method static PracticeMode PRACTICE()
 */
final class ArenaModes
{
	use CloningRegistryTrait;

	/**
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public static function setup(): void
	{
		self::_registryRegister(
			name: "solo",
			member: new SoloMode()
		);
		self::_registryRegister(
			name: "duo",
			member: new DuoMode()
		);
		self::_registryRegister(
			name: "trio",
			member: new TrioMode()
		);
		self::_registryRegister(
			name: "squad",
			member: new SquadMode()
		);
		self::_registryRegister(
			name: "practice",
			member: new PracticeMode()
		);
	}

	/**
	 * The mode class name must be like this:
	 * 
	 * CustomSoloMode
	 * CustomThingMode
	 * CustomSolo
	 * 
	 * basically the class name must contain the mode name
	 * and it must not have the same name as an already registered arena
	 * and it must not contain anything that is not related to the mode
	 * for example:
	 * 
	 * MyGameCustomSoloMode
	 * MyGameCustomThingMode
	 * MyGameCustomSolo
	 * MyGameCustomSoloModeForLaterUse
	 * MyGameCustomSoloModeObject
	 * 
	 * the reason of why you must not do what was shown up is
	 * because it will give the wrong name of what the mode must be called
	 * for example MyGameCustomSoloMode it will be shown as MYGAMECUSTOMSOLO
	 * when doing what was said as the right way of putting the class name
	 * it will show the name rightly
	 * for example the CustomSoloMode will be called CUSTOMSOLO
	 * and that is the right mode name
	 * 
	 * @param ArenaMode $mode
	 */
	public function registerCustomMode(ArenaMode $mode)
	{
		self::_registryRegister(
			name: $mode->name(),
			member: $mode
		);
	}

	/**
	 * @param string $value
	 * @return null|ArenaMode
	 */
	public static function fromString(string $value): ?ArenaMode
	{
		return self::_registryFromString(name: $value) ?? null;
	}
}
