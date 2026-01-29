/**
 * Map Module - Leaflet Integration
 * Handles all map rendering with GPS accuracy visualization
 */

import { select } from './utils.js';

export const maps = {};

/**
 * Initialize Leaflet map with accuracy visualization
 * @param {string} divId - Container element ID
 * @param {number} lat - Latitude
 * @param {number} lng - Longitude
 * @param {number} acc - GPS accuracy in meters
 * @param {object} options - Additional options
 * @returns {object} Leaflet map instance
 */
export const initMap = (divId, lat, lng, acc = 0, options = {}) => {
    if (!lat || !lng || !L) return;
    
    const el = document.getElementById(divId);
    if (!el) return;
    
    // Clean up existing map
    if (maps[divId]) {
        try {
            maps[divId].remove();
        } catch (e) {
            console.error('Error removing map:', e);
        }
        delete maps[divId];
    }
    
    // Configuration
    const config = {
        isRealTime: options.isRealTime ?? false,
        initialZoom: options.initialZoom ?? 19,
        maxZoom: 20,
        minZoom: 12,
        circleVisible: true,
        ...options
    };
    
    // Accuracy tier helpers
    const accuracyTier = (val) => 
        val <= 15 ? 'EXCELLENT' : 
        val <= 50 ? 'GOOD' : 
        val <= 150 ? 'FAIR' : 
        'ACCEPTABLE';
    
    const tierColor = {
        EXCELLENT: '#10b981',
        GOOD: '#3b82f6',
        FAIR: '#f59e0b',
        ACCEPTABLE: '#8b5cf6'
    };
    
    const tierLabel = {
        EXCELLENT: 'Excellent',
        GOOD: 'Good',
        FAIR: 'Fair',
        ACCEPTABLE: 'Acceptable'
    };
    
    const circleColor = tierColor[accuracyTier(acc)];
    
    // Create map
    const map = L.map(divId, {
        zoomControl: true,
        scrollWheelZoom: true,
        dragging: true,
        zoomAnimation: true,
        fadeAnimation: true,
        markerZoomAnimation: true,
        attributionControl: false
    }).setView([lat, lng], config.initialZoom);
    
    // Add tile layer
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 20,
        maxNativeZoom: 19,
        minZoom: 10,
        className: 'map-tiles',
        updateWhenIdle: true,
        keepBuffer: 2,
        attribution: ''
    }).addTo(map);
    
    // Create dynamic icon based on zoom level
    const createDynamicIcon = (zoom) => {
        const isMobile = window.innerWidth <= 768;
        const baseScale = isMobile ? 0.6 : 1.0;
        const scale = Math.max(0.3, Math.min(baseScale, (zoom - 10) / 8));
        const iconWidth = Math.round(25 * scale);
        const iconHeight = Math.round(41 * scale);
        const shadowWidth = Math.round(41 * scale);
        const shadowHeight = Math.round(41 * scale);
        
        return L.icon({
            iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
            iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
            shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
            iconSize: [iconWidth, iconHeight],
            iconAnchor: [Math.round(iconWidth / 2), iconHeight],
            popupAnchor: [0, -iconHeight],
            shadowSize: [shadowWidth, shadowHeight],
            shadowAnchor: [Math.round(shadowWidth / 3), shadowHeight]
        });
    };
    
    // Add marker
    const marker = L.marker([lat, lng], {
        icon: createDynamicIcon(config.initialZoom),
        zIndexOffset: 3000,
        draggable: false,
        keyboard: false,
        riseOnHover: true,
        riseOffset: 250,
        interactive: true,
        bubblingMouseEvents: true
    }).addTo(map);
    
    setTimeout(() => {
        const markerElement = marker.getElement();
        if (markerElement) {
            markerElement.style.pointerEvents = 'auto';
            markerElement.style.cursor = 'pointer';
        }
    }, 100);
    
    // Resize handler
    window.addEventListener('resize', () => {
        if (marker && map._createDynamicIcon) {
            marker.setIcon(map._createDynamicIcon(map.getZoom()));
        }
    });
    
    map._marker = marker;
    map._createDynamicIcon = createDynamicIcon;
    
    let circle = null;
    let isAnimating = false;
    let currentState = { lat, lng, acc };
    
    // Generate tooltip content
    const generateTooltipContent = (accuracy) => {
        const tier = accuracyTier(accuracy);
        const tierText = tierLabel[tier];
        const color = tierColor[tier];
        
        return `<div style="padding:12px 16px;border-radius:10px;background:linear-gradient(135deg,rgba(255,255,255,0.98) 0%,rgba(248,250,252,0.98) 100%);border:1px solid rgba(0,0,0,0.08);box-shadow:0 8px 20px rgba(0,0,0,0.12),0 2px 6px rgba(0,0,0,0.06);backdrop-filter:blur(12px);min-width:220px;max-width:280px">` +
            `<div style="text-align:center;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif">` +
            `<div style="display:flex;align-items:center;justify-content:center;gap:7px;margin-bottom:8px">` +
            `<span style="font-size:1.2rem;line-height:1">📍</span>` +
            `<strong style="color:${color};font-size:0.95rem;font-weight:700;letter-spacing:-0.01em">${tierText} Signal</strong>` +
            `</div>` +
            `<div style="background:linear-gradient(135deg,rgba(0,0,0,0.04) 0%,rgba(0,0,0,0.02) 100%);padding:8px 12px;border-radius:6px;margin:6px 0">` +
            `<div style="color:#64748b;font-size:0.7rem;font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:2px">GPS Accuracy</div>` +
            `<span style="color:#1e293b;font-size:1rem;font-weight:700;display:block">±${Math.round(accuracy)} meters</span>` +
            `</div>` +
            `<div style="margin-top:10px;padding-top:8px;border-top:1px solid rgba(0,0,0,0.06);display:flex;align-items:center;justify-content:center;gap:5px">` +
            `<span style="font-size:0.85rem">✨</span>` +
            `<small style="color:#64748b;font-size:0.75rem;font-weight:500">Click marker to zoom in</small>` +
            `</div>` +
            `</div></div>`;
    };
    
    // Draw accuracy circle
    const drawCircle = (circleLat = currentState.lat, circleLng = currentState.lng, circleAcc = currentState.acc) => {
        const zoom = map.getZoom();
        
        if (circle) {
            try {
                map.removeLayer(circle);
            } catch (e) {
                console.error('Error removing circle:', e);
            }
            circle = null;
        }
        
        if (zoom < 17 || !config.circleVisible || circleAcc <= 0 || circleAcc > 500) {
            return;
        }
        
        const color = tierColor[accuracyTier(circleAcc)];
        const baseOpacity = Math.min(0.95, 0.7 + (zoom - 12) * 0.05);
        const displayRadius = circleAcc;
        
        circle = L.circle([circleLat, circleLng], {
            radius: displayRadius,
            color: color,
            weight: 2.5,
            opacity: Math.min(0.9, baseOpacity + 0.05),
            fill: true,
            fillColor: color,
            fillOpacity: Math.min(0.25, 0.15 + (zoom - 12) * 0.015),
            interactive: false,
            bubblingMouseEvents: false,
            className: 'geo-accuracy-circle',
            pane: 'overlayPane'
        }).addTo(map);
        
        setTimeout(() => {
            const circleElement = circle.getElement();
            const overlayPane = map.getPane('overlayPane');
            const markerPane = map.getPane('markerPane');
            const tooltipPane = map.getPane('tooltipPane');
            const popupPane = map.getPane('popupPane');
            
            if (overlayPane) {
                overlayPane.style.zIndex = '2';
                overlayPane.style.pointerEvents = 'none';
            }
            if (markerPane) {
                markerPane.style.zIndex = '4';
                markerPane.style.pointerEvents = 'auto';
            }
            if (tooltipPane) {
                tooltipPane.style.zIndex = '9999';
                tooltipPane.style.pointerEvents = 'none';
            }
            if (popupPane) {
                popupPane.style.zIndex = '9998';
            }
            if (circleElement) {
                circleElement.style.pointerEvents = 'none';
            }
        }, 50);
    };
    
    // Update position (for real-time tracking)
    map._updatePosition = (newLat, newLng, newAcc) => {
        currentState = { lat: newLat, lng: newLng, acc: newAcc };
        marker.setLatLng([newLat, newLng]);
        marker.setTooltipContent(generateTooltipContent(newAcc));
        
        if (config.isRealTime) {
            map.setView([newLat, newLng], map.getZoom(), { animate: false });
        }
        
        drawCircle(newLat, newLng, newAcc);
    };
    
    // Initial map setup
    setTimeout(() => {
        map.invalidateSize();
        drawCircle();
    }, 80);
    
    // Bind tooltip
    const tier = accuracyTier(acc);
    const tooltipContent = generateTooltipContent(acc);
    
    marker.bindTooltip(tooltipContent, {
        permanent: false,
        direction: 'top',
        offset: [0, -35],
        opacity: 1,
        className: 'custom-marker-tooltip',
        sticky: true
    });
    
    // Marker click - zoom in
    marker.on('click', () => {
        if (isAnimating) return;
        isAnimating = true;
        
        const currentZoom = map.getZoom();
        const targetZoom = Math.max(currentZoom, 20);
        
        if (targetZoom > currentZoom) {
            map.flyTo([currentState.lat, currentState.lng], targetZoom, {
                animate: true,
                duration: 0.8,
                easeLinearity: 0.25
            });
        }
        
        setTimeout(() => {
            isAnimating = false;
        }, 850);
    });
    
    // Zoom event handler
    let zoomTimeout;
    map.on('zoomend', () => {
        clearTimeout(zoomTimeout);
        zoomTimeout = setTimeout(() => {
            const zoom = map.getZoom();
            if (map._createDynamicIcon && marker) {
                marker.setIcon(map._createDynamicIcon(zoom));
            }
            drawCircle();
        }, 50);
    });
    
    // Move event handler (for real-time)
    map.on('moveend', () => {
        if (config.isRealTime && !isAnimating) {
            drawCircle();
        }
    });
    
    maps[divId] = map;
    return map;
};
