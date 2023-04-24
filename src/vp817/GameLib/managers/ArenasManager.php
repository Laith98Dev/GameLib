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

namespace vp817\GameLib\managers;

use vp817\GameLib\arena\Arena;

final class ArenasManager
{
	/** @var Arena[] $loadedArenas */
	private array $loadedArenas = [];

	public function signAsLoaded(string $arenaID, Arena $arena, ?callable $onSuccess = null): void
	{
		if (array_key_exists($arenaID, $this->loadedArenas)) {
			return;
		}

		$this->loadedArenas[$arenaID] = $arena;
		$onSuccess($arenaID, $arena);
	}

	public function unsignFromBeingLoaded(string $arenaID, ?callable $onSuccess = null, ?callable $onFail = null): void
	{
		if (!array_key_exists($arenaID, $this->loadedArenas)) {
			if ($onFail !== null) {
				$onFail($arenaID);
			}
			return;
		}

		unset($this->loadedArenas[$arenaID]);
		$onSuccess($arenaID);
	}

	public function getLoadedArena(string $arenaID, ?callable $onSuccess = null, ?callable $onFail = null): void
	{
		if (!array_key_exists($arenaID, $this->loadedArenas)) {
			if ($onFail !== null) {
				$onFail($arenaID);
			}
			return;
		}
		$onSuccess($this->loadedArenas[$arenaID]);
	}
}