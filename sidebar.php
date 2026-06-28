
<!--CSS-->
<link href="js/jquery/jquery-sidebar/css/sidebar.min.css"
rel="stylesheet"
type="text/css" />

<style>
  .sidebar .content { padding: 15px; }
  #stops-list { list-style: none; padding: 0; margin: 10px 0; }
  #stops-list li { display: flex; align-items: center; gap: 5px; margin-bottom: 8px; cursor: default; }
  .drag-handle { cursor: grab; color: #888; font-size: 16px; padding: 0 2px; }
  .drag-handle:active { cursor: grabbing; }
  #stops-list li.ui-sortable-helper { background: #333; border-radius: 3px; padding: 4px; }
  #stops-list input[type="text"] { flex: 1; padding: 6px 8px; border: 1px solid #555; border-radius: 3px; background: #333; color: #fff; font-size: 14px; }
  #stops-list .remove-stop { background: #cf2828; color: #fff; border: none; padding: 4px 8px; border-radius: 3px; cursor: pointer; font-size: 12px; }
  #optimize-order { background: #e67e22; color: #fff; border: none; padding: 6px 12px; border-radius: 3px; cursor: pointer; font-size: 13px; margin-top: 5px; width: 100%; }
  #optimize-order:hover { opacity: 0.85; }
  #add-stop { background: #4a90d9; color: #fff; border: none; padding: 6px 12px; border-radius: 3px; cursor: pointer; font-size: 13px; margin-top: 5px; }
  #plan-route { background: #2ecc71; color: #fff; border: none; padding: 8px 16px; border-radius: 3px; cursor: pointer; font-size: 14px; margin-top: 15px; width: 100%; font-weight: bold; }
  #add-stop:hover { opacity: 0.85; }
  #plan-route:hover { opacity: 0.85; }
  .stop-number { color: #999; font-size: 12px; min-width: 20px; }
</style>

<!-- ╔══════════════════════════════════════════════════════════════╗
     ║                    LEFT SIDEBAR                             ║
     ║  Contains the trip stops list, add/remove/optimize/plan     ║
     ║  buttons. Slides in from the left when the ☰ button is     ║
     ║  clicked. Uses jquery-sidebar plugin for the slide effect.  ║
     ║                                                             ║
     ║  Layout of each stop <li>:                                  ║
     ║  ┌──┬────┬──────────────────────────┬───┐                   ║
     ║  │☰ │ 1. │ Enter address or city    │ × │                   ║
     ║  └──┴────┴──────────────────────────┴───┘                   ║
     ║  drag  #   text input               remove                 ║
     ╚══════════════════════════════════════════════════════════════╝ -->
<div class="sidebar" id="sidebar">
  <div class="close">
    <a href="#" id="sidebar-close" class="btn-close">Close</a>
  </div>
  <div class="content">
    <h3 style="margin: 0 0 10px 0;">Trip Stops</h3>
    <ul id="stops-list">
      <li>
        <span class="drag-handle">&#9776;</span>
        <span class="stop-number">1.</span>
        <input type="text" class="stop-input" placeholder="Enter address or city">
        <button class="remove-stop">&times;</button>
      </li>
      <li>
        <span class="drag-handle">&#9776;</span>
        <span class="stop-number">2.</span>
        <input type="text" class="stop-input" placeholder="Enter address or city">
        <button class="remove-stop">&times;</button>
      </li>
    </ul>
    <div id="route-info" style="margin: 10px 0; padding: 8px; background: #2a2a2a; border-radius: 3px; font-size: 13px; display: none;">
      <span id="route-distance">—</span> · <span id="route-duration">—</span>
    </div>
    <button id="add-stop">+ Add Stop</button>
    <button id="optimize-order" disabled
      style="opacity: 0.4; cursor: not-allowed;"
      title="Enter 4+ stops, then click to rearrange them for the shortest driving route. First and last stops stay fixed.">Optimize Order</button>
    <button id="plan-route">Plan Route</button>
  </div>
</div>

<!--RIGHT SIDEBAR (settings) — placeholder for future use -->
<div class="sidebar" id="sidebar-right">
  <div class="close">
    <a href="#" id="sidebar-close" class="btn-close">Close</a>
  </div>
  <div class="content">
    Settings
  </div>
</div>

<script>
/**
 * ════════════════════════════════════════════════════════════════
 * TRIP PLANNER SIDEBAR
 * ════════════════════════════════════════════════════════════════
 * Manages the list of trip stops and communicates with the Google
 * Maps iframe to display routes.
 *
 * Three main features:
 *
 * 1. STOP MANAGEMENT — add, remove, drag-to-reorder stops
 * 2. OPTIMIZE ORDER  — asks Google Directions API for the shortest
 *                      driving route, then animates the inputs
 *                      swapping into the optimal order
 * 3. PLAN ROUTE      — reloads the map iframe with the current
 *                      stops, which draws the driving route
 *
 * Data flow:
 * ┌─────────────┐     ┌───────────────────┐     ┌─────────────┐
 * │  Sidebar     │────>│  URL params       │────>│  Map iframe  │
 * │  (inputs)    │     │  ?center=...      │     │  (init-map)  │
 * │              │     │  &addresses=A|B|C │     │              │
 * └─────────────┘     └───────────────────┘     └─────────────┘
 *
 * The sidebar collects input values, joins them with | (pipe)
 * as a delimiter (since addresses contain commas), and sets
 * the iframe src. The map's init-map.js splits on | and uses
 * Google Directions to draw the route.
 */
$(document).ready(function() {

  /**
   * renumber()
   * Updates the visible stop numbers (1., 2., 3., ...) to match
   * the current DOM order. Called after any reorder or removal.
   *
   * Before:          After removing #2:
   * ┌─────────┐     ┌─────────┐
   * │ 1. NYC  │     │ 1. NYC  │
   * │ 2. CHI  │     │ 2. LA   │  <-- was 3, now 2
   * │ 3. LA   │     └─────────┘
   * └─────────┘
   */
  function renumber() {
    $('#stops-list li').each(function(i) {
      $(this).find('.stop-number').text((i + 1) + '.');
    });
    updateOptimizeButton();
  }

  function updateOptimizeButton() {
    var count = 0;
    $('.stop-input').each(function() {
      if ($(this).val().trim()) count++;
    });
    var btn = $('#optimize-order');
    if (count >= 4) {
      btn.prop('disabled', false).css({ opacity: 1, cursor: 'pointer' });
    } else {
      btn.prop('disabled', true).css({ opacity: 0.4, cursor: 'not-allowed' });
    }
  }

  /**
   * updateRouteInfo()
   * Calculates the total driving distance and duration for the
   * current stops and displays it in the #route-info bar.
   * Calls Google Directions API without optimizing — just
   * measures the route in the current order.
   *
   * Called after: plan route, optimize order, add/remove/reorder stops.
   */
  function updateRouteInfo() {
    var stops = [];
    $('.stop-input').each(function() {
      var val = $(this).val().trim();
      if (val) stops.push(val);
    });
    if (stops.length < 2) {
      $('#route-info').hide();
      return;
    }

    var origin = stops[0];
    var destination = stops[stops.length - 1];
    var middleStops = [];
    for (var i = 1; i < stops.length - 1; i++) {
      middleStops.push(stops[i]);
    }

    // Use server-side proxy to hide API key
    var url = 'api/directions.php'
      + '?origin=' + encodeURIComponent(origin)
      + '&destination=' + encodeURIComponent(destination);
    if (middleStops.length > 0) {
      url += '&waypoints=' + encodeURIComponent(middleStops.join('|'));
    }

    $.getJSON(url, function(response) {
      if (response.status !== 'OK') {
        $('#route-info').hide();
        return;
      }
      var totalMeters = 0;
      var totalSeconds = 0;
      var legs = response.routes[0].legs;
      for (var i = 0; i < legs.length; i++) {
        totalMeters += legs[i].distance.value;
        totalSeconds += legs[i].duration.value;
      }
      var miles = (totalMeters / 1609.34).toFixed(1);
      var hours = Math.floor(totalSeconds / 3600);
      var minutes = Math.round((totalSeconds % 3600) / 60);
      var timeStr = hours > 0 ? hours + 'h ' + minutes + 'm' : minutes + 'm';

      $('#route-distance').text(miles + ' miles');
      $('#route-duration').text(timeStr);
      $('#route-info').show();
    });
  }

  /**
   * DRAG-TO-REORDER
   * Uses jQuery UI's sortable() to let users grab the ☰ handle
   * and drag a stop to a new position in the list.
   * The 'axis: y' restricts dragging to vertical only.
   * After dropping, renumber() fixes the stop numbers.
   */
  $('#stops-list').sortable({
    handle: '.drag-handle',
    axis: 'y',
    update: function() { renumber(); updateRouteInfo(); }
  });

  /**
   * ADD STOP
   * Appends a new empty stop input to the bottom of the list.
   * Each stop gets a drag handle, number, text input, and remove button.
   */
  $('#add-stop').on('click', function() {
    var count = $('#stops-list li').length + 1;
    var li = '<li>' +
      '<span class="drag-handle">&#9776;</span>' +
      '<span class="stop-number">' + count + '.</span>' +
      '<input type="text" class="stop-input" placeholder="Enter address or city">' +
      '<button class="remove-stop">&times;</button>' +
      '</li>';
    $('#stops-list').append(li);
  });

  // Update route info and optimize button when a stop input changes
  $('#stops-list').on('change', '.stop-input', function() {
    updateRouteInfo();
    updateOptimizeButton();
  });

  /**
   * REMOVE STOP
   * Removes the clicked stop's <li> from the list and renumbers.
   * Won't remove if only 2 stops remain (need at least 2 for a route).
   * Uses event delegation (on the parent <ul>) so dynamically added
   * stops also get the click handler.
   */
  $('#stops-list').on('click', '.remove-stop', function(e) {
    // Stop the click from bubbling to the document handler in sidebar.js,
    // which would see the removed element as "outside the sidebar" and close it
    e.stopPropagation();
    if ($('#stops-list li').length > 2) {
      $(this).closest('li').remove();
      renumber();
      updateRouteInfo();
    }
  });

  /**
   * ════════════════════════════════════════════════════════════════
   * OPTIMIZE ORDER
   * ════════════════════════════════════════════════════════════════
   * Sends the current stops to Google's Directions API with
   * optimizeWaypoints: true. Google returns the optimal order
   * for the middle stops (origin and destination stay fixed).
   *
   * Then we figure out which swaps are needed to get from the
   * current order to the optimal order, and animate each swap
   * so the user can watch the sorting happen.
   *
   * Example with 5 stops:
   *
   *   Current order:    [NYC, Denver, LA, Chicago, Miami]
   *                      ^^^                        ^^^^
   *                     origin                   destination
   *                     (fixed)                   (fixed)
   *
   *   Google says optimal waypoint order is: [2, 0, 1]
   *   meaning: LA (index 2), Denver (index 0), Chicago (index 1)
   *
   *   New order:        [NYC, LA, Denver, Chicago, Miami]
   *
   *   Swaps needed:
   *     Step 1: swap position 1 (Denver) with position 2 (LA)
   *
   *     ┌─────────┐         ┌─────────┐
   *     │ 1. NYC  │         │ 1. NYC  │
   *     │ 2. DEN ─┼──┐  ┌──│ 2. LA   │
   *     │ 3. LA  ─┼──┘  └──│ 3. DEN  │
   *     │ 4. CHI  │         │ 4. CHI  │
   *     │ 5. MIA  │         │ 5. MIA  │
   *     └─────────┘         └─────────┘
   *      BEFORE              AFTER
   *
   *   The animation shows each pair sliding past each other
   *   vertically before the values are swapped.
   */
  $('#optimize-order').on('click', function() {
    // Collect all non-empty stop values
    var stops = [];
    var inputs = $('.stop-input');
    inputs.each(function() {
      var val = $(this).val().trim();
      if (val) stops.push(val);
    });

    if (stops.length < 4) {
      return;
    }

    var origin = stops[0];
    var destination = stops[stops.length - 1];
    var waypoints = [];
    for (var i = 1; i < stops.length - 1; i++) {
      waypoints.push(stops[i]);
    }

    // Use server-side proxy with optimize flag to get optimal order
    var url = 'api/directions.php'
      + '?origin=' + encodeURIComponent(origin)
      + '&destination=' + encodeURIComponent(destination)
      + '&waypoints=' + encodeURIComponent(waypoints.join('|'))
      + '&optimize=true';

    $.getJSON(url, function(response) {
      if (response.status !== 'OK') {
        alert('Could not optimize: ' + response.status);
        return;
      }

      // ── BUILD THE NEW ORDER ──────────────────────────────────
      // waypoint_order is an array like [2, 0, 1] meaning:
      //   optimal position 0 = original waypoint 2
      //   optimal position 1 = original waypoint 0
      //   optimal position 2 = original waypoint 1
      var order = response.routes[0].waypoint_order;
      var newStops = [origin];
      for (var i = 0; i < order.length; i++) {
        newStops.push(waypoints[order[i]]);
      }
      newStops.push(destination);

      // ── COMPUTE SWAPS ────────────────────────────────────────
      // Walk through the list comparing current vs target.
      // When a position doesn't match, find where the correct
      // value is and record a swap. Apply the swap to our
      // working copy so subsequent comparisons are accurate.
      //
      //  working:  [NYC, DEN, LA, CHI, MIA]
      //  target:   [NYC, LA, DEN, CHI, MIA]
      //
      //  i=0: NYC == NYC, skip
      //  i=1: DEN != LA, find LA at index 2 → swap(1,2)
      //       working becomes [NYC, LA, DEN, CHI, MIA]
      //  i=2: DEN == DEN, skip
      //  ...done, swaps = [[1,2]]
      var items = $('#stops-list li');
      var currentValues = [];
      items.each(function() {
        currentValues.push($(this).find('.stop-input').val());
      });

      var swaps = [];
      var working = currentValues.slice();
      for (var i = 0; i < newStops.length; i++) {
        if (working[i] !== newStops[i]) {
          var fromIndex = working.indexOf(newStops[i]);
          if (fromIndex !== -1) {
            swaps.push([i, fromIndex]);
            var temp = working[i];
            working[i] = working[fromIndex];
            working[fromIndex] = temp;
          }
        }
      }

      // ── ANIMATE SWAPS ────────────────────────────────────────
      // Process swaps one at a time using recursion.
      // Each swap:
      //   1. Highlights both <li> elements
      //   2. Slides them past each other (liA moves down, liB moves up)
      //   3. Resets their CSS positions
      //   4. Swaps the actual input values
      //   5. Calls animateSwap(next) for the next swap
      //
      // Visual of one swap animation:
      //
      //   START          SLIDING          DONE
      //  ┌───────┐     ┌───────┐       ┌───────┐
      //  │ DEN ──┼─┐   │   ↕   │       │  LA   │
      //  │       │ │   │ crossing      │       │
      //  │ LA  ──┼─┘   │   ↕   │       │ DEN   │
      //  └───────┘     └───────┘       └───────┘
      //
      function animateSwap(swapIndex) {
        if (swapIndex >= swaps.length) {
          renumber();
          updateRouteInfo();
          return;
        }

        var a = swaps[swapIndex][0];
        var b = swaps[swapIndex][1];
        var liA = $('#stops-list li').eq(a);
        var liB = $('#stops-list li').eq(b);

        // Calculate how far apart they are vertically.
        // liA needs to move down by deltaY, liB moves up by the same amount.
        var posA = liA.position();
        var posB = liB.position();
        var deltaY = posB.top - posA.top;

        // Highlight both items so the user can see which ones are swapping
        liA.css({ 'position': 'relative', 'z-index': 10, 'background': '#3a3a3a', 'border-radius': '3px' });
        liB.css({ 'position': 'relative', 'z-index': 10, 'background': '#3a3a3a', 'border-radius': '3px' });

        // Slide liA down and liB up simultaneously.
        // The second .animate() callback fires when both are done.
        liA.animate({ top: deltaY }, 400);
        liB.animate({ top: -deltaY }, 400, function() {
          // Reset the CSS so they sit in their natural DOM positions
          liA.css({ top: 0, position: '', 'z-index': '', background: '', 'border-radius': '' });
          liB.css({ top: 0, position: '', 'z-index': '', background: '', 'border-radius': '' });

          // Swap the actual text values in the inputs.
          // The DOM order doesn't change — only the values move.
          var valA = liA.find('.stop-input').val();
          var valB = liB.find('.stop-input').val();
          liA.find('.stop-input').val(valB);
          liB.find('.stop-input').val(valA);

          // Recurse to the next swap
          animateSwap(swapIndex + 1);
        });
      }

      animateSwap(0);
    });
  });

  /**
   * ════════════════════════════════════════════════════════════════
   * PLAN ROUTE
   * ════════════════════════════════════════════════════════════════
   * Collects all stop values and reloads the map iframe with them.
   * The map's init-map.js uses Google Directions to draw the
   * driving route with markers at each stop.
   *
   * URL format:
   *   map/index.php?center=Rockford%2C+IL&addresses=Rockford%2C+IL|Mason+City%2C+IA
   *
   * Addresses are joined with | (pipe) not comma, because
   * addresses themselves contain commas (e.g. "Rockford, IL").
   */
  $('#plan-route').on('click', function() {
    var stops = [];
    $('.stop-input').each(function() {
      var val = $(this).val().trim();
      if (val) stops.push(val);
    });
    if (stops.length < 2) {
      alert('Enter at least 2 stops');
      return;
    }
    // Center the map on the first stop
    var center = stops[0];
    // Encode each stop individually but keep | as the delimiter
    var addresses = stops.map(function(s) { return encodeURIComponent(s); }).join('|');
    var iframe = document.getElementById('map');
    iframe.src = 'map/index.php?center=' + encodeURIComponent(center) + '&addresses=' + addresses;
    updateRouteInfo();
  });
});
</script>
