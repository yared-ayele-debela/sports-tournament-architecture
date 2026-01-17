#!/bin/bash

# =============================================================================
# Auth Service API Test Suite
# =============================================================================
# Developer: Sports Tournament System Team
# Description: Comprehensive testing script for Authentication Service
# Version: 1.0.0
# Usage: ./test-auth-service.sh
# Prerequisites: jq, curl, auth-service running on localhost:8001
# =============================================================================

set -e  # Exit on any error

# Configuration
BASE_URL="${AUTH_SERVICE_URL:-http://localhost:8001/api}"
TIMEOUT=30
VERBOSE=false

# Test data (dynamic to avoid conflicts)
TIMESTAMP=$(date +%s)
EMAIL="devtest_${TIMESTAMP}@sports-tournament.com"
PASSWORD="SecurePass123!"
NAME="Developer Test User"

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Global variables
TOKEN=""
USER_ID=""

# =============================================================================
# Utility Functions
# =============================================================================

# Print header
print_header() {
    echo -e "\n${BLUE}============================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}============================================${NC}"
}

# Print section
print_section() {
    echo -e "\n${CYAN}📋 $1${NC}"
    echo -e "${CYAN}----------------------------------------${NC}"
}

# Print success
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

# Print error
print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Print info
print_info() {
    echo -e "${YELLOW}ℹ️  $1${NC}"
}

# Print debug (if verbose)
print_debug() {
    if [ "$VERBOSE" = true ]; then
        echo -e "${YELLOW}🐛 DEBUG: $1${NC}"
    fi
}

# Test result checker
check_result() {
    local status_code=$1
    local test_name=$2
    local expected_status=${3:-200}
    
    if [ "$status_code" = "$expected_status" ]; then
        print_success "$test_name (HTTP $status_code)"
        return 0
    else
        print_error "$test_name (HTTP $status_code, expected $expected_status)"
        return 1
    fi
}

# Show JSON response (if verbose)
show_response() {
    if [ "$VERBOSE" = true ] && [ -f "$1" ]; then
        echo -e "${YELLOW}📄 Response:${NC}"
        cat "$1" | jq . 2>/dev/null || cat "$1"
        echo ""
    fi
}

# =============================================================================
# API Test Functions
# =============================================================================

test_health_check() {
    print_section "Health Check"
    
    local response_file="health_response.json"
    local http_code=$(curl -s -w "%{http_code}" -o "$response_file" \
        --max-time "$TIMEOUT" \
        -X GET \
        "$BASE_URL/health" \
        -H "Content-Type: application/json")
    
    if check_result "$http_code" "Health Check"; then
        local service_name=$(cat "$response_file" | jq -r '.service' 2>/dev/null || echo "unknown")
        print_info "Service: $service_name is healthy"
    fi
    
    show_response "$response_file"
    return $([ "$http_code" = "200" ] && echo 0 || echo 1)
}

test_service_info() {
    print_section "Service Information"
    
    local response_file="info_response.json"
    local http_code=$(curl -s -w "%{http_code}" -o "$response_file" \
        --max-time "$TIMEOUT" \
        -X GET \
        "$BASE_URL/info" \
        -H "Content-Type: application/json")
    
    if check_result "$http_code" "Service Info"; then
        local version=$(cat "$response_file" | jq -r '.version' 2>/dev/null || echo "unknown")
        print_info "Service Version: $version"
    fi
    
    show_response "$response_file"
    return $([ "$http_code" = "200" ] && echo 0 || echo 1)
}

test_user_registration() {
    print_section "User Registration"
    
    print_info "Creating test user: $EMAIL"
    
    local response_file="register_response.json"
    local http_code=$(curl -s -w "%{http_code}" -o "$response_file" \
        --max-time "$TIMEOUT" \
        -X POST \
        "$BASE_URL/auth/register" \
        -H "Content-Type: application/json" \
        -d "{
            \"name\": \"$NAME\",
            \"email\": \"$EMAIL\",
            \"password\": \"$PASSWORD\",
            \"password_confirmation\": \"$PASSWORD\"
        }")
    
    if check_result "$http_code" "User Registration" "201"; then
        TOKEN=$(cat "$response_file" | jq -r '.data.token' 2>/dev/null || echo "")
        USER_ID=$(cat "$response_file" | jq -r '.data.user.id' 2>/dev/null || echo "")
        print_info "User ID: $USER_ID"
        print_info "Token received: ${TOKEN:0:50}..."
    fi
    
    show_response "$response_file"
    return $([ "$http_code" = "201" ] && echo 0 || echo 1)
}

test_user_login() {
    print_section "User Login"
    
    print_info "Authenticating: $EMAIL"
    
    local response_file="login_response.json"
    local http_code=$(curl -s -w "%{http_code}" -o "$response_file" \
        --max-time "$TIMEOUT" \
        -X POST \
        "$BASE_URL/auth/login" \
        -H "Content-Type: application/json" \
        -d "{
            \"email\": \"$EMAIL\",
            \"password\": \"$PASSWORD\"
        }")
    
    if check_result "$http_code" "User Login"; then
        TOKEN=$(cat "$response_file" | jq -r '.data.token' 2>/dev/null || echo "")
        USER_ID=$(cat "$response_file" | jq -r '.data.user.id' 2>/dev/null || echo "")
        print_info "New Token: ${TOKEN:0:50}..."
    fi
    
    show_response "$response_file"
    return $([ "$http_code" = "200" ] && echo 0 || echo 1)
}

test_user_profile() {
    print_section "Get User Profile"
    
    if [ -z "$TOKEN" ]; then
        print_error "No token available for profile test"
        return 1
    fi
    
    print_info "Fetching profile with token"
    
    local response_file="profile_response.json"
    local http_code=$(curl -s -w "%{http_code}" -o "$response_file" \
        --max-time "$TIMEOUT" \
        -X GET \
        "$BASE_URL/auth/me" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Content-Type: application/json")
    
    if check_result "$http_code" "Get User Profile"; then
        local user_name=$(cat "$response_file" | jq -r '.data.user.name' 2>/dev/null || echo "unknown")
        local user_email=$(cat "$response_file" | jq -r '.data.user.email' 2>/dev/null || echo "unknown")
        local roles_count=$(cat "$response_file" | jq -r '.data.roles | length' 2>/dev/null || echo "0")
        print_info "User: $user_name ($user_email)"
        print_info "Roles: $roles_count"
    fi
    
    show_response "$response_file"
    return $([ "$http_code" = "200" ] && echo 0 || echo 1)
}

test_token_refresh() {
    print_section "Token Refresh"
    
    if [ -z "$TOKEN" ]; then
        print_error "No token available for refresh test"
        return 1
    fi
    
    print_info "Refreshing current token"
    
    local response_file="refresh_response.json"
    local http_code=$(curl -s -w "%{http_code}" -o "$response_file" \
        --max-time "$TIMEOUT" \
        -X POST \
        "$BASE_URL/auth/refresh" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Content-Type: application/json")
    
    if check_result "$http_code" "Token Refresh"; then
        local new_token=$(cat "$response_file" | jq -r '.data.token' 2>/dev/null || echo "")
        if [ -n "$new_token" ] && [ "$new_token" != "null" ]; then
            TOKEN=$new_token
            print_info "Token refreshed successfully"
            print_info "New Token: ${TOKEN:0:50}..."
        fi
    fi
    
    show_response "$response_file"
    return $([ "$http_code" = "200" ] && echo 0 || echo 1)
}

test_user_logout() {
    print_section "User Logout"
    
    if [ -z "$TOKEN" ]; then
        print_error "No token available for logout test"
        return 1
    fi
    
    print_info "Logging out user"
    
    local response_file="logout_response.json"
    local http_code=$(curl -s -w "%{http_code}" -o "$response_file" \
        --max-time "$TIMEOUT" \
        -X POST \
        "$BASE_URL/auth/logout" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Content-Type: application/json")
    
    if check_result "$http_code" "User Logout"; then
        print_info "Logout successful"
        TOKEN=""  # Clear token after logout
    fi
    
    show_response "$response_file"
    return $([ "$http_code" = "200" ] && echo 0 || echo 1)
}

test_invalid_token() {
    print_section "Invalid Token Test (Expected Failure)"
    
    local response_file="invalid_response.json"
    local http_code=$(curl -s -w "%{http_code}" -o "$response_file" \
        --max-time "$TIMEOUT" \
        -X GET \
        "$BASE_URL/auth/me" \
        -H "Authorization: Bearer invalid_token_here" \
        -H "Content-Type: application/json")
    
    if check_result "$http_code" "Invalid Token Rejection" "401"; then
        print_info "Invalid token properly rejected"
    fi
    
    show_response "$response_file"
    return $([ "$http_code" = "401" ] && echo 0 || echo 1)
}

test_internal_endpoints() {
    print_section "Internal Service Endpoints"
    
    if [ -z "$TOKEN" ]; then
        print_error "No token available for internal endpoints test"
        return 1
    fi
    
    # Test user validation
    print_info "Testing user validation endpoint"
    local response_file="validate_response.json"
    local http_code=$(curl -s -w "%{http_code}" -o "$response_file" \
        --max-time "$TIMEOUT" \
        -X POST \
        "$BASE_URL/users/validate" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Content-Type: application/json" \
        -d "{\"user_id\": $USER_ID}")
    
    if check_result "$http_code" "User Validation"; then
        local exists=$(cat "$response_file" | jq -r '.exists' 2>/dev/null || echo "false")
        print_info "User exists: $exists"
    fi
    
    show_response "$response_file"
    return $([ "$http_code" = "200" ] && echo 0 || echo 1)
}

# =============================================================================
# Cleanup Function
# =============================================================================

cleanup() {
    print_section "Cleanup"
    print_info "Removing temporary files..."
    rm -f *.json
    print_success "Cleanup completed"
}

# =============================================================================
# Main Execution
# =============================================================================

main() {
    print_header "Auth Service API Test Suite"
    
    print_info "Configuration:"
    print_info "  Base URL: $BASE_URL"
    print_info "  Timeout: ${TIMEOUT}s"
    print_info "  Test Email: $EMAIL"
    print_info "  Verbose: $VERBOSE"
    
    # Set trap for cleanup
    trap cleanup EXIT
    
    local failed_tests=0
    local total_tests=0
    
    # Run tests
    test_health_check || ((failed_tests++))
    ((total_tests++))
    
    test_service_info || ((failed_tests++))
    ((total_tests++))
    
    test_user_registration || ((failed_tests++))
    ((total_tests++))
    
    test_user_login || ((failed_tests++))
    ((total_tests++))
    
    test_user_profile || ((failed_tests++))
    ((total_tests++))
    
    test_token_refresh || ((failed_tests++))
    ((total_tests++))
    
    test_user_logout || ((failed_tests++))
    ((total_tests++))
    
    test_invalid_token || ((failed_tests++))
    ((total_tests++))
    
    test_internal_endpoints || ((failed_tests++))
    ((total_tests++))
    
    # Summary
    print_header "Test Results Summary"
    local passed_tests=$((total_tests - failed_tests))
    
    echo -e "Total Tests: $total_tests"
    print_success "Passed: $passed_tests"
    
    if [ $failed_tests -gt 0 ]; then
        print_error "Failed: $failed_tests"
        echo -e "\n${RED}🚨 Some tests failed! Check the logs above.${NC}"
        exit 1
    else
        print_success "All tests passed! 🎉"
        echo -e "\n${GREEN}🚀 Auth Service is ready for production!${NC}"
    fi
}

# =============================================================================
# Script Entry Point
# =============================================================================

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -v|--verbose)
            VERBOSE=true
            shift
            ;;
        -u|--url)
            BASE_URL="$2/api"
            shift 2
            ;;
        -h|--help)
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  -v, --verbose    Enable verbose output"
            echo "  -u, --url URL    Set custom base URL (default: http://localhost:8001)"
            echo "  -h, --help       Show this help message"
            echo ""
            echo "Environment Variables:"
            echo "  AUTH_SERVICE_URL    Override base URL"
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            echo "Use -h for help"
            exit 1
            ;;
    esac
done

# Check dependencies
if ! command -v jq &> /dev/null; then
    print_error "jq is required but not installed. Install with: sudo apt install jq"
    exit 1
fi

if ! command -v curl &> /dev/null; then
    print_error "curl is required but not installed. Install with: sudo apt install curl"
    exit 1
fi

# Run main function
main
