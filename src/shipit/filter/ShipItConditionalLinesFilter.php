<?hh // strict
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the same directory.
 */

namespace Facebook\ShipIt;

/**
 * Comments or uncomments specially marked lines.
 *
 * Eg if:
 *  - comment start is '//'
 *  - comment end is null
 *  - marker is '@oss-disable'
 *
 * commentLines():
 *  - foo() // @oss-disable
 *  + // @oss-disable: foo()
 * uncommentLines():
 *  - // @oss-disable: foo()
 *  + foo() // @oss-disable
 */
final class ShipItConditionalLinesFilter {
  public static function commentLines(
    ShipItChangeset $changeset,
    string $marker,
    string $comment_start,
    ?string $comment_end = null,
  ): ShipItChangeset {
    $pattern =
      '/^([-+ ]\s*)(.+) '.
      preg_quote($comment_start, '/').
      ' '.
      preg_quote($marker, '/').
      ($comment_end === null ? '' : (' '.preg_quote($comment_end, '/'))).
      '$/m';

    $replacement = '\\1'.$comment_start.' '.$marker.': \\2';
    if ($comment_end !== null) {
      $replacement .= ' '.$comment_end;
    }

    return self::process($changeset, $pattern, $replacement);
  }

  public static function uncommentLines(
    ShipItChangeset $changeset,
    string $marker,
    string $comment_start,
    ?string $comment_end = null,
  ): ShipItChangeset {
    $pattern =
      '/^([-+ ]\s*)'.
      preg_quote($comment_start, '/').
      ' '.
      preg_quote($marker, '/').
      ': (.+)'.
      ($comment_end === null ? '' : (' '.preg_quote($comment_end, '/'))).
      '$/m';
    $replacement = '\\1\\2 '.$comment_start.' '.$marker;
    if ($comment_end !== null) {
      $replacement .= ' '.$comment_end;
    }

    return self::process($changeset, $pattern, $replacement);
  }

  private static function process(
    ShipItChangeset $changeset,
    string $pattern,
    string $replacement,
  ): ShipItChangeset {
    $diffs = Vector { };
    foreach ($changeset->getDiffs() as $diff) {
      $diff['body'] = preg_replace(
        $pattern,
        $replacement,
        $diff['body'],
      );
      $diffs[] = $diff;
    }
    return $changeset->withDiffs($diffs->toImmVector());
  }
}
