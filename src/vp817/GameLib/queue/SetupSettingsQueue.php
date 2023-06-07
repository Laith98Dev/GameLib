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

namespace vp817\GameLib\queue;

use pocketmine\entity\Location;
use function array_key_exists;
use function json_encode;

final class SetupSettingsQueue
{

	/** @var array $queue */
	private array $queue = [
		"spawnsQueue" => []
	];

	/**
	 * @param int $number
	 * @param Location $location
	 * @return void
	 */
	public function setSpawn(int $number, Location $location): void
	{
		if (array_key_exists($number, $this->queue["spawnsQueue"])) {
			return;
		}

		$this->queue["spawnsQueue"][$number] = [
			"x" => $location->getX(),
			"y" => $location->getY(),
			"z" => $location->getZ(),
			"yaw" => $location->getYaw(),
			"pitch" => $location->getPitch()
		];
	}

	/**
	 * @param string $worldName
	 * @param Location $location
	 * @return void
	 */
	public function setLobbySettings(string $worldName, Location $location): void
	{
		if (array_key_exists("lobbySettingsQueue", $this->queue)) {
			return;
		}

		$this->queue["lobbySettingsQueue"] = json_encode([
			"worldName" => $worldName,
			"location" => [
				"x" => $location->getX(),
				"y" => $location->getY(),
				"z" => $location->getZ(),
				"yaw" => $location->getYaw(),
				"pitch" => $location->getPitch()
			]
		]);
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function setArenaData(array $data): void
	{
		if (array_key_exists("arenaDataQueue", $this->queue)) {
			return;
		}

		$this->queue["arenaDataQueue"] = json_encode($data);
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function setExtraData(array $data): void
	{
		if ($this->hasExtraData()) {
			return;
		}

		$this->queue["extraDataQueue"] = json_encode($data);
	}

	/**
	 * @return array
	 */
	public function getSpawns(): array
	{
		return $this->queue["spawnsQueue"];
	}

	/**
	 * @return string
	 */
	public function getLobbySettings(): string
	{
		return $this->queue["lobbySettingsQueue"];
	}

	/**
	 * @return string
	 */
	public function getArenaData(): string
	{
		return $this->queue["arenaDataQueue"];
	}

	/**
	 * @return string
	 */
	public function getExtraData(): string
	{
		return $this->queue["extraDataQueue"];
	}

	/**
	 * @return bool
	 */
	public function hasExtraData(): bool
	{
		return array_key_exists("extraDataQueue", $this->queue);
	}

	/**
	 * @return void
	 */
	public function clear(): void
	{
		foreach ($this->queue["spawnsQueue"] as $key => $value) {
			unset($this->queue["spawnsQueue"][$key]);
		}
		unset($this->queue["spawnsQueue"]);
		unset($this->queue["lobbySettingsQueue"]);
		unset($this->queue["arenaData"]);
		if ($this->hasExtraData()) {
			unset($this->queue["extraDataQueue"]);
		}
	}
}
