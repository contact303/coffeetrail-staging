(function ($) {
    'use strict';

    const EXTRA_RADIUS_METERS = 10000;

    let resultsCircle = null;
    let initializedMap = null;
    let initAttempts = 0;

    /**
     * בדיקה האם לערך של פילטר יש תוכן.
     */
    function hasFilterValue(value) {
        if (Array.isArray(value)) {
            return value.length > 0;
        }

        if (value && typeof value === 'object') {
            return Object.keys(value).length > 0;
        }

        return value !== undefined &&
            value !== null &&
            value !== '' &&
            value !== false;
    }

    /**
     * קבלת הפילטרים הפעילים של Explore.
     */
    function getActiveFilters() {
        if (
            !window.MyListing ||
            !MyListing.Explore ||
            !MyListing.Explore.activeType ||
            !MyListing.Explore.activeType.filters
        ) {
            return {};
        }

        return MyListing.Explore.activeType.filters;
    }

    /**
     * קבלת ערך מהפילטרים, עם fallback לכתובת העמוד.
     */
    function getFilterValue(key) {
        const filters = getActiveFilters();

        if (hasFilterValue(filters[key])) {
            return filters[key];
        }

        return new URLSearchParams(window.location.search).get(key);
    }

    /**
     * האם צריך להציג את רדיוס תוצאות האזור.
     */
    function shouldDrawCircle() {
        const region = getFilterValue('region');
        const sort = getFilterValue('sort');

        return hasFilterValue(region) && sort !== 'nearby';
    }

    /**
     * הסרת העיגול הקיים.
     */
    function removeResultsCircle(map) {
        if (!resultsCircle) {
            return;
        }

        try {
            map.removeCircle(resultsCircle);
        } catch (error) {
            if (typeof resultsCircle.remove === 'function') {
                resultsCircle.remove();
            }
        }

        resultsCircle = null;
    }

    /**
     * איסוף מיקומי המרקרים שמוצגים כרגע.
     */
    function getMarkerPositions(map) {
        if (!Array.isArray(map.markers)) {
            return [];
        }

        const positions = [];
        const uniquePositions = new Set();

        map.markers.forEach(function (marker) {
            if (!marker || typeof marker.getPosition !== 'function') {
                return;
            }

            const position = marker.getPosition();

            if (
                !position ||
                typeof position.getLatitude !== 'function' ||
                typeof position.getLongitude !== 'function'
            ) {
                return;
            }

            const lat = parseFloat(position.getLatitude());
            const lng = parseFloat(position.getLongitude());

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            /*
             * מניעת ספירה כפולה של אותה נקודה.
             */
            const key = lat.toFixed(7) + ',' + lng.toFixed(7);

            if (uniquePositions.has(key)) {
                return;
            }

            uniquePositions.add(key);

            positions.push({
                lat: lat,
                lng: lng
            });
        });

        return positions;
    }

    /**
     * ציור רדיוס סביב כל תוצאות החיפוש.
     */
    function drawResultsCircle() {
        if (
            !window.MyListing ||
            !MyListing.Explore ||
            !MyListing.Explore.map
        ) {
            return;
        }

        const map = MyListing.Explore.map;

        removeResultsCircle(map);

        if (!shouldDrawCircle()) {
            return;
        }

        const positions = getMarkerPositions(map);

        if (!positions.length) {
            return;
        }

        const leafletPoints = positions.map(function (position) {
            return L.latLng(position.lat, position.lng);
        });

        /*
         * מרכז התחום של כל המרקרים.
         */
        const center = L.latLngBounds(leafletPoints).getCenter();

        /*
         * המרחק הגדול ביותר ממרכז התחום לאחת התוצאות.
         */
        let maxDistance = 0;

        leafletPoints.forEach(function (point) {
            const distance = center.distanceTo(point);

            if (distance > maxDistance) {
                maxDistance = distance;
            }
        });

        /*
         * תוספת קבועה של 10 ק"מ.
         * אם יש רק תוצאה אחת, הרדיוס יהיה בדיוק 10 ק"מ.
         */
        const radiusMeters = maxDistance + EXTRA_RADIUS_METERS;
        const radiusKilometers = radiusMeters / 1000;

        const centerPosition = new MyListing.Maps.LatLng(
            center.lat,
            center.lng
        );

        /*
         * שימוש בפונקציה המובנית של MyListing,
         * כדי לקבל את אותם צבעים וסגנון של רדיוס Nearby.
         */
        resultsCircle = map.setCircle(
            centerPosition,
            radiusKilometers,
            'km',
            map
        );
    }

    /**
     * חיבור לאירוע עדכון המרקרים של MyListing.
     */
    function initialize() {
        initAttempts++;

        if (
            !window.MyListing ||
            !MyListing.Explore ||
            !MyListing.Explore.map ||
            typeof MyListing.Explore.map.addListener !== 'function'
        ) {
            if (initAttempts < 100) {
                window.setTimeout(initialize, 200);
            }

            return;
        }

        const map = MyListing.Explore.map;

        /*
         * מניעת רישום כפול של האירוע.
         */
        if (initializedMap === map) {
            return;
        }

        initializedMap = map;

        map.addListener('updated_markers', function () {
            /*
             * האירוע נורה מתוך updateMap.
             * ההשהיה מאפשרת ל-MyListing לסיים את עדכון המפה.
             */
            window.setTimeout(drawResultsCircle, 20);
        });

        /*
         * טיפול בתוצאות שכבר נטענו לפני שהקוד אותחל.
         */
        window.setTimeout(drawResultsCircle, 100);
    }

    $(document).ready(initialize);

    /*
     * במקרה שבו ספריית המפות נטענה לאחר DOM Ready.
     */
    $(document).on('maps:loaded', initialize);

})(jQuery);