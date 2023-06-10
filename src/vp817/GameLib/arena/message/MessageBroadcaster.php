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

namespace vp817\GameLib\arena\message;

use vp817\GameLib\arena\Arena;
use function is_null;

class MessageBroadcaster
{

	/**
	 * @param Arena $arena
	 */
	public function __construct(protected Arena $arena)
	{
	}

	/**
	 * @param string $function
	 * @param string $argument
	 * @return void
	 */
	public function broadcast(string $function, string $argument = ""): void
	{
		$players = $this->arena->getMode()->getPlayers();

		foreach ($players as $bytes => $arenaPlayer) {
			$cells = $arenaPlayer->getCells();
			if (is_null($cells)) return;
			if (!$cells->isOnline()) return;

			if (strlen(trim($argument)) < 1) {
				$cells->$function();
				return;
			}

			$cells->$function($argument);
		}
	}

	/**
	 * @param string $value
	 * @return void
	 */
	public function broadcastMessage(string $value): void
	{
		$this->broadcast("sendMessage", $value);
	}

	/**
	 * @param string $value
	 * @return void
	 */
	public function broadcastPopup(string $value): void
	{
		$this->broadcast("sendPopup", $value);
	}

	/**
	 * @param string $value
	 * @return void
	 */
	public function broadcastTip(string $value): void
	{
		$this->broadcast("sendTip", $value);
	}

	/**
	 * @param string $value
	 * @return void
	 */
	public function broadcastTitle(string $value): void
	{
		$this->broadcast("sendTitle", $value);
	}

	/**
	 * @param string $value
	 * @return void
	 */
	public function broadcastSubTitle(string $value): void
	{
		$this->broadcast("sendSubTitle", $value);
	}

	/**
	 * @param string $value
	 * @return void
	 */
	public function broadcastActionBarMessage(string $value): void
	{
		$this->broadcast("sendActionBarMessage", $value);
	}

	/**
	 * @return void
	 */
	public function broadcastTitlesClear(): void
	{
		$this->broadcast("removeTitles");
	}

	/**
	 * @return void
	 */
	public function broadcastTitlesReset(): void
	{
		$this->broadcast("resetTitles");
	}
}
