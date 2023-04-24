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
use vp817\GameLib\arena\modes\list\SoloMode;
use vp817\GameLib\arena\modes\list\SquadMode;
use vp817\GameLib\arena\modes\list\TrioMode;

/**
 * @method static SoloMode SOLO()
 * @method static DuoMode DUO()
 * @method static TrioMode TRIO()
 * @method static SquadMode SQUAD()
 */
final class ArenaModes
{
	use CloningRegistryTrait;

	/**
	 * @return void
	 */
	public static function setup(): void
	{
		self::_registryRegister("solo", new SoloMode());
		self::_registryRegister("duo", new DuoMode());
		self::_registryRegister("trio", new TrioMode());
		self::_registryRegister("squad", new SquadMode());
	}

	/**
	 * @param string $value
	 * @return ArenaMode
	 */
	public static function fromString(string $value): ArenaMode
	{
		return match ($value) {
			"solo"   => static::SOLO(),
			"duo"    => static::DUO(),
			"trio"   => static::TRIO(),
			"squand" => static::SQUAD()
		};
	}
}