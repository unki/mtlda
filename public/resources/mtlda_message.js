/**
 * This file is part of MTLDA.
 *
 * MTLDA, a web-based document archive.
 * Copyright (C) <2015>  <Andreas Unterkircher>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 */

var MtldaMessage = function () {
    return true;

    var command;
    var message;
};

MtldaMessage.prototype.setCommand = function (command) {
    this.command = command;
    return true;
}

MtldaMessage.prototype.setMessage = function (message) {
    this.message = message;
    return true;
}

// vim: set filetype=javascript expandtab softtabstop=4 tabstop=4 shiftwidth=4:
