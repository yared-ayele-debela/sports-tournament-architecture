#!/bin/bash

# Team Service API Test Script - Working Version
# This script tests all endpoints of the team service with proper authentication

# Configuration
TEAM_SERVICE_URL="http://127.0.0.1:8003"
AUTH_SERVICE_URL="http://127.0.0.1:8001"
TOURNAMENT_SERVICE_URL="http://127.0.0.1:8002"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Global variables
ACCESS_TOKEN=""
TOURNAMENT_ID=""
TEAM_ID=""
PLAYER_ID=""
COACH_ID=""

# Helper functions
print_header() {
    echo -e "\n${BLUE}=====================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}=====================================${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ $1${NC}"
}

# Function to check if service is running
check_service() {
    local url=$1
    local service_name=$2
    
    print_info "Checking if $service_name is running at $url..."
    
    if curl -s -f "$url/api/health" > /dev/null; then
        print_success "$service_name is running"
        return 0
    else
        print_error "$service_name is not running or not accessible"
        return 1
    fi
}

# Function to authenticate and get token
authenticate() {
    print_header "Authentication"
    
    print_info "Attempting to login with admin user..."
    
    LOGIN_RESPONSE=$(curl -s -X POST "$AUTH_SERVICE_URL/api/auth/login" \
        -H "Content-Type: application/json" \
        -d '{
            "email": "admin1@test.com",
            "password": "password"
        }')
    
    if echo "$LOGIN_RESPONSE" | grep -q '"success":true'; then
        ACCESS_TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
        print_success "Login successful"
        print_info "Access token: ${ACCESS_TOKEN:0:30}..."
        
        # Get user ID for coach
        COACH_ID=$(echo "$LOGIN_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
        print_info "User ID (will use as coach): $COACH_ID"
    else
        print_error "Login failed"
        echo "$LOGIN_RESPONSE"
        exit 1
    fi
}

# Function to get a tournament ID
get_tournament_id() {
    print_header "Getting Tournament ID"
    
    print_info "Fetching tournaments from tournament service..."
    
    TOURNAMENTS_RESPONSE=$(curl -s -X GET "$TOURNAMENT_SERVICE_URL/api/tournaments" \
        -H "Authorization: Bearer $ACCESS_TOKEN")
    
    if echo "$TOURNAMENTS_RESPONSE" | grep -q '"success":true'; then
        # Check if there are tournaments
        TOURNAMENT_COUNT=$(echo "$TOURNAMENTS_RESPONSE" | grep -o '"id":[0-9]*' | wc -l)
        if [ "$TOURNAMENT_COUNT" -gt 0 ]; then
            TOURNAMENT_ID=$(echo "$TOURNAMENTS_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
            print_success "Found tournament ID: $TOURNAMENT_ID"
        else
            print_info "No tournaments found, creating a test tournament..."
            CREATE_TOURNAMENT_RESPONSE=$(curl -s -X POST "$TOURNAMENT_SERVICE_URL/api/tournaments" \
                -H "Content-Type: application/json" \
                -H "Authorization: Bearer $ACCESS_TOKEN" \
                -d '{
                    "sport_id": 1,
                    "name": "Test Tournament",
                    "location": "Test Location",
                    "start_date": "2026-01-20",
                    "end_date": "2026-01-25"
                }')
            
            if echo "$CREATE_TOURNAMENT_RESPONSE" | grep -q '"success":true'; then
                TOURNAMENT_ID=$(echo "$CREATE_TOURNAMENT_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
                print_success "Created tournament with ID: $TOURNAMENT_ID"
            else
                print_error "Failed to create tournament"
                echo "$CREATE_TOURNAMENT_RESPONSE"
                exit 1
            fi
        fi
    else
        print_error "Failed to fetch tournaments"
        echo "$TOURNAMENTS_RESPONSE"
        exit 1
    fi
}

# Test Teams endpoints
test_teams() {
    print_header "Testing Teams Endpoints"
    
    # Test List Teams
    print_info "Testing GET /api/tournaments/$TOURNAMENT_ID/teams..."
    TEAMS_RESPONSE=$(curl -s -X GET "$TEAM_SERVICE_URL/api/tournaments/$TOURNAMENT_ID/teams" \
        -H "Authorization: Bearer $ACCESS_TOKEN")
    
    if echo "$TEAMS_RESPONSE" | grep -q '"success":true'; then
        print_success "List teams endpoint working"
    else
        print_error "List teams endpoint failed"
        echo "$TEAMS_RESPONSE"
    fi
    
    # Test Create Team
    print_info "Testing POST /api/teams..."
    CREATE_TEAM_RESPONSE=$(curl -s -X POST "$TEAM_SERVICE_URL/api/teams" \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $ACCESS_TOKEN" \
        -d "{
            \"tournament_id\": $TOURNAMENT_ID,
            \"name\": \"Test Team $(date +%s)\",
            \"logo\": \"https://example.com/logo.png\",
            \"coach_id\": $COACH_ID
        }")
    
    if echo "$CREATE_TEAM_RESPONSE" | grep -q '"success":true'; then
        TEAM_ID=$(echo "$CREATE_TEAM_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
        print_success "Team created successfully with ID: $TEAM_ID"
        
        # Test Get Team
        print_info "Testing GET /api/teams/$TEAM_ID..."
        GET_TEAM_RESPONSE=$(curl -s -X GET "$TEAM_SERVICE_URL/api/teams/$TEAM_ID" \
            -H "Authorization: Bearer $ACCESS_TOKEN")
        
        if echo "$GET_TEAM_RESPONSE" | grep -q '"success":true'; then
            print_success "Get team endpoint working"
        else
            print_error "Get team endpoint failed"
            echo "$GET_TEAM_RESPONSE"
        fi
        
        # Test Update Team
        print_info "Testing PUT /api/teams/$TEAM_ID..."
        UPDATE_TEAM_RESPONSE=$(curl -s -X PUT "$TEAM_SERVICE_URL/api/teams/$TEAM_ID" \
            -H "Content-Type: application/json" \
            -H "Authorization: Bearer $ACCESS_TOKEN" \
            -d '{
                "name": "Updated Test Team",
                "logo": "https://example.com/updated-logo.png"
            }')
        
        if echo "$UPDATE_TEAM_RESPONSE" | grep -q '"success":true'; then
            print_success "Update team endpoint working"
        else
            print_error "Update team endpoint failed"
            echo "$UPDATE_TEAM_RESPONSE"
        fi
        
        # Test Get Team Players
        print_info "Testing GET /api/teams/$TEAM_ID/players..."
        TEAM_PLAYERS_RESPONSE=$(curl -s -X GET "$TEAM_SERVICE_URL/api/teams/$TEAM_ID/players" \
            -H "Authorization: Bearer $ACCESS_TOKEN")
        
        if echo "$TEAM_PLAYERS_RESPONSE" | grep -q '"success":true'; then
            print_success "Get team players endpoint working"
        else
            print_error "Get team players endpoint failed"
            echo "$TEAM_PLAYERS_RESPONSE"
        fi
        
    else
        print_error "Create team endpoint failed"
        echo "$CREATE_TEAM_RESPONSE"
    fi
}

# Test Players endpoints
test_players() {
    print_header "Testing Players Endpoints"
    
    if [ -z "$TEAM_ID" ]; then
        print_error "No team ID available for player tests"
        return
    fi
    
    # Test List Players
    print_info "Testing GET /api/players..."
    PLAYERS_RESPONSE=$(curl -s -X GET "$TEAM_SERVICE_URL/api/players" \
        -H "Authorization: Bearer $ACCESS_TOKEN")
    
    if echo "$PLAYERS_RESPONSE" | grep -q '"success":true'; then
        print_success "List players endpoint working"
    else
        print_error "List players endpoint failed"
        echo "$PLAYERS_RESPONSE"
    fi
    
    # Test Create Player
    print_info "Testing POST /api/players..."
    CREATE_PLAYER_RESPONSE=$(curl -s -X POST "$TEAM_SERVICE_URL/api/players" \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $ACCESS_TOKEN" \
        -d "{
            \"team_id\": $TEAM_ID,
            \"full_name\": \"Test Player $(date +%s)\",
            \"position\": \"Midfielder\",
            \"jersey_number\": 10
        }")
    
    if echo "$CREATE_PLAYER_RESPONSE" | grep -q '"success":true'; then
        PLAYER_ID=$(echo "$CREATE_PLAYER_RESPONSE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
        print_success "Player created successfully with ID: $PLAYER_ID"
        
        # Test Get Player
        print_info "Testing GET /api/players/$PLAYER_ID..."
        GET_PLAYER_RESPONSE=$(curl -s -X GET "$TEAM_SERVICE_URL/api/players/$PLAYER_ID" \
            -H "Authorization: Bearer $ACCESS_TOKEN")
        
        if echo "$GET_PLAYER_RESPONSE" | grep -q '"success":true'; then
            print_success "Get player endpoint working"
        else
            print_error "Get player endpoint failed"
            echo "$GET_PLAYER_RESPONSE"
        fi
        
        # Test Update Player
        print_info "Testing PUT /api/players/$PLAYER_ID..."
        UPDATE_PLAYER_RESPONSE=$(curl -s -X PUT "$TEAM_SERVICE_URL/api/players/$PLAYER_ID" \
            -H "Content-Type: application/json" \
            -H "Authorization: Bearer $ACCESS_TOKEN" \
            -d '{
                "full_name": "Updated Test Player",
                "position": "Forward",
                "jersey_number": 11
            }')
        
        if echo "$UPDATE_PLAYER_RESPONSE" | grep -q '"success":true'; then
            print_success "Update player endpoint working"
        else
            print_error "Update player endpoint failed"
            echo "$UPDATE_PLAYER_RESPONSE"
        fi
        
        # Test Delete Player
        print_info "Testing DELETE /api/players/$PLAYER_ID..."
        DELETE_PLAYER_RESPONSE=$(curl -s -X DELETE "$TEAM_SERVICE_URL/api/players/$PLAYER_ID" \
            -H "Authorization: Bearer $ACCESS_TOKEN")
        
        if echo "$DELETE_PLAYER_RESPONSE" | grep -q '"success":true'; then
            print_success "Delete player endpoint working"
        else
            print_error "Delete player endpoint failed"
            echo "$DELETE_PLAYER_RESPONSE"
        fi
        
    else
        print_error "Create player endpoint failed"
        echo "$CREATE_PLAYER_RESPONSE"
    fi
}

# Test error cases
test_error_cases() {
    print_header "Testing Error Cases"
    
    # Test invalid tournament ID
    print_info "Testing with invalid tournament ID..."
    INVALID_TOURNAMENT_RESPONSE=$(curl -s -X POST "$TEAM_SERVICE_URL/api/teams" \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $ACCESS_TOKEN" \
        -d "{
            \"tournament_id\": 99999,
            \"name\": \"Invalid Team\",
            \"coach_id\": $COACH_ID
        }")
    
    if echo "$INVALID_TOURNAMENT_RESPONSE" | grep -q '"success":false'; then
        print_success "Invalid tournament ID properly rejected"
    else
        print_error "Invalid tournament ID not properly handled"
    fi
    
    # Test invalid coach ID
    print_info "Testing with invalid coach ID..."
    INVALID_COACH_RESPONSE=$(curl -s -X POST "$TEAM_SERVICE_URL/api/teams" \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $ACCESS_TOKEN" \
        -d "{
            \"tournament_id\": $TOURNAMENT_ID,
            \"name\": \"Invalid Coach Team\",
            \"coach_id\": 99999
        }")
    
    if echo "$INVALID_COACH_RESPONSE" | grep -q '"success":false'; then
        print_success "Invalid coach ID properly rejected"
    else
        print_error "Invalid coach ID not properly handled"
    fi
    
    # Test unauthorized access
    print_info "Testing unauthorized access..."
    UNAUTHORIZED_RESPONSE=$(curl -s -X GET "$TEAM_SERVICE_URL/api/teams")
    
    if echo "$UNAUTHORIZED_RESPONSE" | grep -q '"unauthenticated\|Unauthorized\|401"'; then
        print_success "Unauthorized access properly blocked"
    else
        print_error "Unauthorized access not properly handled"
    fi
}

# Cleanup test data
cleanup() {
    print_header "Cleanup"
    
    if [ -n "$TEAM_ID" ]; then
        print_info "Deleting test team $TEAM_ID..."
        DELETE_TEAM_RESPONSE=$(curl -s -X DELETE "$TEAM_SERVICE_URL/api/teams/$TEAM_ID" \
            -H "Authorization: Bearer $ACCESS_TOKEN")
        
        if echo "$DELETE_TEAM_RESPONSE" | grep -q '"success":true'; then
            print_success "Test team deleted successfully"
        else
            print_error "Failed to delete test team"
        fi
    fi
}

# Main execution
main() {
    print_header "Team Service API Test Suite"
    
    # Check if all services are running
    if ! check_service "$TEAM_SERVICE_URL" "Team Service"; then
        exit 1
    fi
    
    if ! check_service "$AUTH_SERVICE_URL" "Auth Service"; then
        exit 1
    fi
    
    if ! check_service "$TOURNAMENT_SERVICE_URL" "Tournament Service"; then
        exit 1
    fi
    
    # Setup
    authenticate
    get_tournament_id
    
    # Run tests
    test_teams
    test_players
    test_error_cases
    
    # Cleanup
    cleanup
    
    print_header "Test Suite Complete"
    print_success "All tests completed!"
}

# Run main function
main "$@"
