<?php
/**
 * cronHelper - Utility script to avoid cron job overlap
 *
 * Copyright (c) 2009-2010, Abhinav Singh <me@abhinavsingh.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Abhinav Singh nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRIC
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package mod_newsletter
 * @author Abhinav Singh <me@abhinavsingh.com>
 * @copyright Abhinav Singh
 * @link http://abhinavsingh.com/blog/2009/12/how-to-use-locks-in-php-cron-jobs-to-avoid-cron-overlaps/
 */

abstract class cron_helper {

    private static $pid;

    private static function is_running() {
        $uname = strtolower(php_uname());
        if (strpos($uname, "darwin") !== false || strpos($uname, "linux") !== false) {
            $pids = explode(PHP_EOL, `ps -e | awk '{print $1}'`);
            return in_array(self::$pid, $pids);
        } else if (strpos($uname, "win") !== false) {
            $pid = self::$pid;
            $output = `TASKLIST /FI "PID eq $pid" /V /NH`;
            return (strpos(self::$pid, $output[0]) !== false);
        }
        return false;
    }

    public static function lock() {

        if (!is_dir(NEWSLETTER_LOCK_DIR)) {
            mkdir(NEWSLETTER_LOCK_DIR, 0777, true);
        }

        $lock_file = NEWSLETTER_LOCK_DIR . '/' . NEWSLETTER_LOCK_SUFFIX;

        if (file_exists($lock_file)) {
            self::$pid = file_get_contents($lock_file);
            if (self::is_running()) {
                // echo "==".self::$pid."== Already in progress...\n";
                return false;
            } else {
                // echo "==".self::$pid."== Previous job died abruptly...\n";
            }
        }

        self::$pid = getmypid();
        file_put_contents($lock_file, self::$pid);
        // echo "==".self::$pid."== Lock acquired, processing the job...\n";
        return self::$pid;
    }

    public static function unlock() {

        $lock_file = NEWSLETTER_LOCK_DIR . '/' . NEWSLETTER_LOCK_SUFFIX;

        if (file_exists($lock_file)) {
            unlink($lock_file);
        }

        // echo "==".self::$pid."== Releasing lock...\n";
        return true;
    }
}
