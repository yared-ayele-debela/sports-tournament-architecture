// Non-module version for direct script loading
(function() {
    'use strict';
    
    // React and ReactDOM will be loaded from CDN
    let React, ReactDOM;
    
    // Wait for React to be available
    function waitForReact() {
        if (typeof window.React !== 'undefined' && typeof window.ReactDOM !== 'undefined') {
            React = window.React;
            ReactDOM = window.ReactDOM;
            initComponent();
        } else {
            setTimeout(waitForReact, 100);
        }
    }
    
    function initComponent() {
        const { useState, useEffect, useRef } = React;
        
        // Configure axios defaults
        if (window.axios) {
            window.axios.defaults.headers.common['X-CSRF-TOKEN'] = window.laravelData?.csrfToken;
        }
        
        const MatchEventManager = () => {
            const [events, setEvents] = useState([]);
            const [homeScore, setHomeScore] = useState(0);
            const [awayScore, setAwayScore] = useState(0);
            const [currentMinute, setCurrentMinute] = useState(0);
            const [loading, setLoading] = useState(false);
            const [connected, setConnected] = useState(false);
            const [newEvent, setNewEvent] = useState({
                player_id: '',
                team_id: '',
                event_type: 'goal',
                minute: '',
                description: ''
            });

            const matchId = window.laravelData?.matchId;
            const homeTeam = window.laravelData?.homeTeam;
            const awayTeam = window.laravelData?.awayTeam;
            const homePlayers = window.laravelData?.homePlayers || [];
            const awayPlayers = window.laravelData?.awayPlayers || [];
            const websocketRef = useRef(null);

            // Initialize with existing events
            useEffect(() => {
                const initialEvents = window.laravelData?.initialEvents || [];
                setEvents(initialEvents);
                
                // Calculate initial scores
                const homeGoals = initialEvents.filter(e => e.team_id === homeTeam.id && e.event_type === 'goal').length;
                const awayGoals = initialEvents.filter(e => e.team_id === awayTeam.id && e.event_type === 'goal').length;
                setHomeScore(homeGoals);
                setAwayScore(awayGoals);
            }, []);

            // WebSocket connection for real-time updates
            useEffect(() => {
                const connectWebSocket = () => {
                    try {
                        const wsUrl = `ws://${window.location.host}/ws/match/${matchId}`;
                        const ws = new WebSocket(wsUrl);
                        websocketRef.current = ws;

                        ws.onopen = () => {
                            console.log('WebSocket connected');
                            setConnected(true);
                        };

                        ws.onmessage = (event) => {
                            const data = JSON.parse(event.data);
                            handleWebSocketMessage(data);
                        };

                        ws.onclose = () => {
                            console.log('WebSocket disconnected');
                            setConnected(false);
                            // Attempt to reconnect after 3 seconds
                            setTimeout(connectWebSocket, 3000);
                        };

                        ws.onerror = (error) => {
                            console.error('WebSocket error:', error);
                            setConnected(false);
                        };
                    } catch (error) {
                        console.error('Failed to connect WebSocket:', error);
                        setConnected(false);
                    }
                };

                connectWebSocket();

                return () => {
                    if (websocketRef.current) {
                        websocketRef.current.close();
                    }
                };
            }, [matchId]);

            // Fallback polling if WebSocket fails
            useEffect(() => {
                if (!connected) {
                    const interval = setInterval(() => {
                        fetchEvents();
                    }, 5000); // Poll every 5 seconds

                    return () => clearInterval(interval);
                }
            }, [connected]);

            const handleWebSocketMessage = (data) => {
                switch (data.type) {
                    case 'new_event':
                        setEvents(prev => [...prev, data.event]);
                        updateScores([...events, data.event]);
                        break;
                    case 'event_updated':
                        setEvents(prev => prev.map(e => e.id === data.event.id ? data.event : e));
                        break;
                    case 'event_deleted':
                        setEvents(prev => prev.filter(e => e.id !== data.eventId));
                        updateScores(events.filter(e => e.id !== data.eventId));
                        break;
                    case 'score_updated':
                        setHomeScore(data.homeScore);
                        setAwayScore(data.awayScore);
                        updateScoreDisplay(data.homeScore, data.awayScore);
                        break;
                    case 'minute_updated':
                        setCurrentMinute(data.minute);
                        updateMinuteDisplay(data.minute);
                        break;
                    case 'status_updated':
                        // Handle match status changes
                        if (data.status === 'completed') {
                            alert('Match has been completed!');
                        }
                        break;
                    default:
                        console.log('Unknown message type:', data.type);
                }
            };

            const fetchEvents = async () => {
                try {
                    const response = await window.axios.get(`/api/matches/${matchId}/events`);
                    if (response.data.success) {
                        setEvents(response.data.data);
                        updateScores(response.data.data);
                    }
                } catch (error) {
                    console.error('Error fetching events:', error);
                }
            };

            const updateScores = (eventsData) => {
                const homeGoals = eventsData.filter(e => e.team_id === homeTeam.id && e.event_type === 'goal').length;
                const awayGoals = eventsData.filter(e => e.team_id === awayTeam.id && e.event_type === 'goal').length;
                setHomeScore(homeGoals);
                setAwayScore(awayGoals);
                updateScoreDisplay(homeGoals, awayGoals);
            };

            const updateScoreDisplay = (homeGoals, awayGoals) => {
                const homeScoreEl = document.getElementById('homeScore');
                const awayScoreEl = document.getElementById('awayScore');
                if (homeScoreEl) homeScoreEl.textContent = homeGoals;
                if (awayScoreEl) awayScoreEl.textContent = awayGoals;
            };

            const updateMinuteDisplay = (minute) => {
                const minuteEl = document.getElementById('currentMinute');
                if (minuteEl) minuteEl.textContent = minute;
            };

            const handleInputChange = (e) => {
                const { name, value } = e.target;
                setNewEvent(prev => ({
                    ...prev,
                    [name]: value
                }));
            };

            const handleSubmit = async (e) => {
                e.preventDefault();
                setLoading(true);

                try {
                    const response = await window.axios.post(`/api/matches/${matchId}/events`, newEvent);
                    if (response.data.success) {
                        const newEventData = response.data.data;
                        setEvents(prev => [...prev, newEventData]);
                        updateScores([...events, newEventData]);
                        
                        // Broadcast to WebSocket if connected
                        if (websocketRef.current && websocketRef.current.readyState === WebSocket.OPEN) {
                            websocketRef.current.send(JSON.stringify({
                                type: 'new_event',
                                event: newEventData
                            }));
                        }
                        
                        setNewEvent({
                            player_id: '',
                            team_id: '',
                            event_type: 'goal',
                            minute: '',
                            description: ''
                        });
                    }
                } catch (error) {
                    console.error('Error adding event:', error);
                    alert('Failed to add event');
                } finally {
                    setLoading(false);
                }
            };

            const deleteEvent = async (eventId) => {
                if (!confirm('Are you sure you want to delete this event?')) return;

                try {
                    const response = await window.axios.delete(`/api/matches/${matchId}/events/${eventId}`);
                    if (response.data.success) {
                        setEvents(prev => prev.filter(e => e.id !== eventId));
                        updateScores(events.filter(e => e.id !== eventId));
                        
                        // Broadcast to WebSocket if connected
                        if (websocketRef.current && websocketRef.current.readyState === WebSocket.OPEN) {
                            websocketRef.current.send(JSON.stringify({
                                type: 'event_deleted',
                                eventId: eventId
                            }));
                        }
                    }
                } catch (error) {
                    console.error('Error deleting event:', error);
                    alert('Failed to delete event');
                }
            };

            const updateMinute = async () => {
                const minute = prompt('Enter current minute:', currentMinute);
                if (minute && !isNaN(minute)) {
                    try {
                        await window.axios.put(`/api/matches/${matchId}/minute`, { current_minute: parseInt(minute) });
                        setCurrentMinute(parseInt(minute));
                        updateMinuteDisplay(parseInt(minute));
                        
                        // Broadcast to WebSocket if connected
                        if (websocketRef.current && websocketRef.current.readyState === WebSocket.OPEN) {
                            websocketRef.current.send(JSON.stringify({
                                type: 'minute_updated',
                                minute: parseInt(minute)
                            }));
                        }
                    } catch (error) {
                        console.error('Error updating minute:', error);
                    }
                }
            };

            const getEventIcon = (eventType) => {
                switch (eventType) {
                    case 'goal':
                        return '⚽';
                    case 'yellow_card':
                        return '🟨';
                    case 'red_card':
                        return '🟥';
                    case 'substitution':
                        return '🔄';
                    default:
                        return '📝';
                }
            };

            const getEventColor = (eventType) => {
                switch (eventType) {
                    case 'goal':
                        return 'bg-green-100 text-green-800 border-green-300';
                    case 'yellow_card':
                        return 'bg-yellow-100 text-yellow-800 border-yellow-300';
                    case 'red_card':
                        return 'bg-red-100 text-red-800 border-red-300';
                    case 'substitution':
                        return 'bg-blue-100 text-blue-800 border-blue-300';
                    default:
                        return 'bg-gray-100 text-gray-800 border-gray-300';
                }
            };

            const getPlayersForTeam = (teamId) => {
                return teamId === homeTeam.id ? homePlayers : awayPlayers;
            };

            return React.createElement('div', { className: 'space-y-6' },
                // Connection Status
                React.createElement('div', {
                    className: `rounded-lg p-3 ${connected ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'} border`
                },
                    React.createElement('div', { className: 'flex items-center' },
                        React.createElement('div', {
                            className: `w-3 h-3 rounded-full mr-2 ${connected ? 'bg-green-500' : 'bg-red-500'}`
                        }),
                        React.createElement('span', {
                            className: `text-sm font-medium ${connected ? 'text-green-800' : 'text-red-800'}`
                        }, connected ? 'Real-time Connected' : 'Polling Mode (No WebSocket)')
                    )
                ),

                // Add Event Form
                React.createElement('div', { className: 'bg-gray-50 rounded-lg p-4' },
                    React.createElement('h3', { className: 'text-lg font-semibold mb-4' }, 'Add Match Event'),
                    React.createElement('form', { 
                        onSubmit: handleSubmit,
                        className: 'grid grid-cols-1 md:grid-cols-6 gap-4'
                    },
                        // Team Select
                        React.createElement('select', {
                            name: 'team_id',
                            value: newEvent.team_id,
                            onChange: handleInputChange,
                            className: 'px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500',
                            required: true
                        },
                            React.createElement('option', { value: '' }, 'Select Team'),
                            React.createElement('option', { value: homeTeam.id }, homeTeam.name),
                            React.createElement('option', { value: awayTeam.id }, awayTeam.name)
                        ),
                        
                        // Event Type Select
                        React.createElement('select', {
                            name: 'event_type',
                            value: newEvent.event_type,
                            onChange: handleInputChange,
                            className: 'px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500',
                            required: true
                        },
                            React.createElement('option', { value: 'goal' }, 'Goal'),
                            React.createElement('option', { value: 'yellow_card' }, 'Yellow Card'),
                            React.createElement('option', { value: 'red_card' }, 'Red Card'),
                            React.createElement('option', { value: 'substitution' }, 'Substitution')
                        ),
                        
                        // Player Select
                        React.createElement('select', {
                            name: 'player_id',
                            value: newEvent.player_id,
                            onChange: handleInputChange,
                            className: 'px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500'
                        },
                            React.createElement('option', { value: '' }, 'Select Player'),
                            ...(newEvent.team_id ? getPlayersForTeam(newEvent.team_id).map(player =>
                                React.createElement('option', { key: player.id, value: player.id },
                                    `${player.full_name} (#${player.jersey_number})`
                                )
                            ) : [])
                        ),
                        
                        // Minute Input
                        React.createElement('input', {
                            type: 'number',
                            name: 'minute',
                            value: newEvent.minute,
                            onChange: handleInputChange,
                            placeholder: 'Minute',
                            min: '1',
                            max: '120',
                            className: 'px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500',
                            required: true
                        }),
                        
                        // Description Input
                        React.createElement('input', {
                            type: 'text',
                            name: 'description',
                            value: newEvent.description,
                            onChange: handleInputChange,
                            placeholder: 'Description (optional)',
                            className: 'px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500'
                        }),
                        
                        // Submit Button
                        React.createElement('button', {
                            type: 'submit',
                            disabled: loading,
                            className: 'px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:opacity-50'
                        }, loading ? 'Adding...' : 'Add Event')
                    )
                ),

                // Current Minute Control
                React.createElement('div', { className: 'bg-blue-50 rounded-lg p-4' },
                    React.createElement('div', { className: 'flex items-center justify-between' },
                        React.createElement('div', null,
                            React.createElement('h4', { className: 'font-semibold' }, `Current Minute: ${currentMinute}`),
                            React.createElement('p', { className: 'text-sm text-gray-600' }, 'Click to update match time')
                        ),
                        React.createElement('button', {
                            onClick: updateMinute,
                            className: 'px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600'
                        }, 'Update Minute')
                    )
                ),

                // Events Timeline
                React.createElement('div', { className: 'event-timeline' },
                    React.createElement('h3', { className: 'text-lg font-semibold mb-4' }, 'Event Timeline'),
                    events.length === 0 ?
                        React.createElement('p', { className: 'text-gray-500 text-center py-8' }, 'No events recorded yet')
                    :
                        React.createElement('div', { className: 'space-y-2' },
                            events.sort((a, b) => a.minute - b.minute).map(event =>
                                React.createElement('div', {
                                    key: event.id,
                                    className: `event-item border rounded-lg p-3 ${getEventColor(event.event_type)}`
                                },
                                    React.createElement('div', { className: 'flex items-center justify-between' },
                                        React.createElement('div', { className: 'flex items-center space-x-3' },
                                            React.createElement('span', { className: 'text-2xl' }, getEventIcon(event.event_type)),
                                            React.createElement('div', null,
                                                React.createElement('div', { className: 'font-semibold' },
                                                    `${event.event_type.replace('_', ' ').toUpperCase()}${event.player ? ` - ${event.player.full_name} (${event.player.jersey_number})` : ''}`
                                                ),
                                                React.createElement('div', { className: 'text-sm text-gray-600' },
                                                    `${event.team?.name || 'Unknown Team'} • Minute ${event.minute}${event.description ? ` • ${event.description}` : ''}`
                                                )
                                            )
                                        ),
                                        React.createElement('button', {
                                            onClick: () => deleteEvent(event.id),
                                            className: 'px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-sm'
                                        }, 'Delete')
                                    )
                                )
                            )
                        )
                )
            );
        };

        // Mount the React component
        const container = document.getElementById('react-match-events');
        if (container) {
            const root = ReactDOM.createRoot(container);
            root.render(React.createElement(MatchEventManager));
        }
    }
    
    // Start waiting for React
    waitForReact();
})();
