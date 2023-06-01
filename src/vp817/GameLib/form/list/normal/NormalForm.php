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

namespace vp817\GameLib\form\list\normal;

use Closure;
use vp817\GameLib\form\FormInterface;
use function is_null;

final class NormalForm extends FormInterface
{

	/**
	 * @param string $title
	 * @param string $contents
	 * @param null|Closure $xButtonCallback
	 */
	public function __construct(string $title, string $content, ?Closure $xButtonCallback = null)
	{
		parent::__construct($xButtonCallback);

		$this->data = [
			"type" => "form",
			"title" => $title,
			"content" => $content,
			"buttons" => []
		];
	}

	/**
	 * @param string $text
	 * @param Closure $onUse
	 * @param null|FormButtonImageType $imageType
	 * @param string $imagePath
	 * @return void
	 */
	public function pushButton(string $text, Closure $onUse, ?FormButtonImageType $imageType = null, string $imagePath = ""): void
	{
		$button = ["text" => $text];
		if (!is_null($imageType)) {
			$button["image"]["type"] = $imageType->name();
			$button["image"]["data"] = $imagePath;
		}
		$this->data["buttons"][] = $button;

		if (!is_null($onUse)) $this->onUseList[] = $onUse;
	}
}
