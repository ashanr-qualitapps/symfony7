# Live Application Resource Monitor

## Overview
The Live Application Resource Monitor provides real-time system monitoring directly in the Symfony dashboard. It displays key system metrics including CPU usage, memory consumption, disk space, and network activity.

## Features

### Real-time Monitoring
- **CPU Usage**: Shows current CPU load averages (1min, 5min, 15min) and estimated percentage
- **Memory Usage**: Displays both PHP memory usage and system memory (when available)
- **Disk Usage**: Shows disk space utilization for the application directory
- **Network**: Monitors active connections and response times

### Interactive Charts
- **CPU & Memory Chart**: Real-time line chart showing CPU and memory usage over time
- **Network Chart**: Displays network connections and response times with dual Y-axis
- **Auto-updating**: Charts update every 3 seconds with the latest 20 data points

### Controls
- **Live Status Indicator**: Shows current monitoring status (Live, Paused, Error, Auth Required)
- **Pause/Resume**: Toggle monitoring on/off without page refresh
- **Progress Bars**: Visual indicators for each metric with color-coded values

## Technical Implementation

### Backend API
- **Endpoint**: `/api/system/resources`
- **Controller**: `App\Controller\Api\SystemResourcesController`
- **Authentication**: Requires `ROLE_USER` access
- **Format**: Returns JSON with system metrics

### Frontend Components
- **Chart.js**: Used for rendering real-time charts
- **Bootstrap**: Provides responsive UI components
- **Vanilla JavaScript**: Handles data fetching and UI updates

### Data Collection Methods
- **CPU**: Uses `sys_getloadavg()` for load averages
- **Memory**: Combines `memory_get_usage()` and system memory info
- **Disk**: Uses `disk_total_space()` and `disk_free_space()`
- **Network**: Attempts to read system connection counts
- **PHP**: Reads configuration and extension information

## Browser Compatibility
- **Modern Browsers**: Chrome 60+, Firefox 55+, Safari 12+, Edge 79+
- **Features**: Uses modern JavaScript (async/await, fetch API)
- **Fallback**: Provides demo data if API calls fail

## Configuration

### Update Frequency
Default: 3 seconds (3000ms)
Location: `startResourceMonitoring()` function

### Chart Data Points
Default: 20 data points (1 minute at 3-second intervals)
Location: `updateCharts()` function

### Error Handling
- Network errors display fallback demo data
- Authentication errors show appropriate status
- Charts continue updating with last known data

## Security Considerations
- API endpoint requires user authentication
- System information is limited to safe, non-sensitive metrics
- No direct system command execution from frontend
- CSRF protection through Symfony's built-in mechanisms

## Performance Impact
- Minimal server load (lightweight PHP functions)
- Client-side charts use efficient rendering
- Background monitoring can be paused to reduce load
- Automatic cleanup on page unload

## Troubleshooting

### Common Issues
1. **Auth Required Status**: User session expired, refresh page
2. **Error Status**: Check server logs and network connectivity
3. **No Data**: Verify API endpoint is accessible and server is running
4. **Slow Updates**: Check network latency and server performance

### Debugging
- Browser console shows detailed logging
- Server logs available in `var/log/`
- Route verification: `php bin/console debug:router | grep api_system_resources`

## Customization

### Adding New Metrics
1. Extend `SystemResourcesController::getSystemResources()`
2. Update the frontend `updateResourceDisplay()` function
3. Add new chart datasets if needed

### Styling
- CSS classes in dashboard template `stylesheets` block
- Bootstrap utility classes for responsive design
- Chart.js configuration in JavaScript section

### Update Intervals
Modify `startResourceMonitoring()` interval parameter to change update frequency.

## Future Enhancements
- Database query performance metrics
- HTTP request/response statistics
- Error rate monitoring
- Custom alert thresholds
- Export monitoring data
- Historical data storage
