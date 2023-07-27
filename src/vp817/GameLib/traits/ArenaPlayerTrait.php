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

namespace vp817\GameLib\traits;

use Closure;
use pocketmine\player\Player;
use vp817\GameLib\player\ArenaPlayer;
use function array_key_exists;
use function is_null;

trait ArenaPlayerTrait
{

	private array $list = [];

	/**
	 * @param Player $player
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function add(Player $player, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		$bytes = $player->getUniqueId()->getBytes();
		if ($this->has($bytes)) {
			if (!is_null($onFail)) $onFail();
			return;
		}

		$this->list[$bytes] = new ArenaPlayer(player: $player);

		if (!is_null($onSuccess)) $onSuccess($this->list[$bytes]);
	}

	/**
	 * @param string $bytes
	 * @param null|Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function remove(string $bytes, ?Closure $onSuccess = null, ?Closure $onFail = null): void
	{
		if (!$this->has($bytes)) {
			if (!is_null($onFail)) $onFail();
			return;
		}

		unset($this->list[$bytes]);

		if (!is_null($onSuccess)) $onSuccess();
	}

	/**
	 * @param string $bytes
	 * @param Closure $onSuccess
	 * @param null|Closure $onFail
	 * @return void
	 */
	public function get(string $bytes, Closure $onSuccess, ?Closure $onFail = null): void
	{
		if (!$this->has($bytes)) {
			if (!is_null($onFail)) $onFail();
			return;
		}

		$onSuccess($this->list[$bytes]);
	}

	/**
	 * @param string $bytes
	 * @return bool
	 */
	public function has(string $bytes): bool
	{
		return array_key_exists($bytes, $this->list);
	}

	/**
	 * @return ArenaPlayer[]
	 */
	public function getAll(): array
	{
		return $this->list;
	}
}
