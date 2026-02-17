# Mermaid Sequence Diagrams

## 1. Match Completion Flow

```mermaid
sequenceDiagram
    autonumber
    participant Client
    participant MatchSvc as Match Service
    participant Auth as Auth Service
    participant DB as Match Database
    participant Redis as Redis Queue
    participant ResultsSvc as Results Service
    participant ResultsDB as Results Database
    participant TournamentSvc as Tournament Service

    Client->>MatchSvc: POST /api/matches/{id}/report<br/>(Bearer Token + Match Report)
    
    Note over MatchSvc: ValidatePassportToken Middleware
    MatchSvc->>Auth: Validate Token<br/>(GET /api/auth/me)
    Auth-->>MatchSvc: User Data + Permissions
    
    alt User Authorized
        MatchSvc->>MatchSvc: Validate Report Data<br/>(summary, referee, scores, etc.)
        
        MatchSvc->>DB: Create/Update MatchReport
        DB-->>MatchSvc: Report Created
        
        MatchSvc->>DB: Update Match Status<br/>(status = 'completed', scores)
        DB-->>MatchSvc: Match Updated
        
        Note over MatchSvc: Determine Result<br/>(home_win/away_win/draw)
        MatchSvc->>MatchSvc: Calculate Result<br/>(home_score vs away_score)
        
        MatchSvc->>Redis: Publish match.completed event<br/>(Queue: events, Priority: HIGH)
        Redis-->>MatchSvc: Event Published
        
        MatchSvc-->>Client: 201 Created<br/>(Match Report Data)
        
        Note over Redis: Asynchronous Event Processing
        Redis->>ResultsSvc: match.completed event
        
        Note over ResultsSvc: MatchCompletedHandler
        ResultsSvc->>ResultsSvc: Validate Payload<br/>(match_id, scores, teams, etc.)
        
        ResultsSvc->>ResultsDB: Create/Update MatchResult<br/>(match_id, scores, completed_at)
        ResultsDB-->>ResultsSvc: MatchResult Saved
        
        Note over ResultsSvc: StandingsCalculator
        ResultsSvc->>ResultsDB: Update Standings<br/>(points, wins, losses, draws, goals)
        ResultsDB-->>ResultsSvc: Standings Updated
        
        ResultsSvc->>Redis: Publish standings.updated event
        ResultsSvc->>Redis: Publish statistics.updated event
        
        Note over Redis: Event Distribution
        Redis->>TournamentSvc: standings.updated event
        Redis->>TournamentSvc: statistics.updated event
        
    else User Not Authorized
        MatchSvc-->>Client: 401 Unauthorized
    end
```

## 2. User Authentication Flow

```mermaid
sequenceDiagram
    autonumber
    participant Client
    participant AuthSvc as Auth Service
    participant DB as Auth Database
    participant Redis as Redis Queue

    Client->>AuthSvc: POST /api/auth/login<br/>(email, password)
    
    AuthSvc->>AuthSvc: Validate Input<br/>(email format, password length)
    
    alt Validation Passes
        AuthSvc->>DB: Query User<br/>(SELECT * FROM users WHERE email = ?)
        DB-->>AuthSvc: User Data
        
        AuthSvc->>AuthSvc: Verify Password<br/>(Hash::check(password, user.password))
        
        alt Credentials Valid
            AuthSvc->>AuthSvc: Generate Access Token<br/>(createToken('Personal Access Token'))
            AuthSvc->>DB: Store Token<br/>(oauth_access_tokens table)
            DB-->>AuthSvc: Token Stored
            
            AuthSvc->>Redis: Publish user.logged.in event<br/>(Queue: events, Priority: LOW)
            Redis-->>AuthSvc: Event Published
            
            AuthSvc-->>Client: 200 Success<br/>(user data + Bearer token)
            
            Note over Client: Store Token<br/>(for subsequent requests)
        else Invalid Credentials
            AuthSvc-->>Client: 401 Unauthorized<br/>("Invalid credentials")
        end
    else Validation Fails
        AuthSvc-->>Client: 422 Validation Error<br/>(field errors)
    end
```

## 3. User Registration Flow (Bonus)

```mermaid
sequenceDiagram
    autonumber
    participant Client
    participant AuthSvc as Auth Service
    participant DB as Auth Database
    participant Redis as Redis Queue

    Client->>AuthSvc: POST /api/auth/register<br/>(name, email, password, password_confirmation)
    
    AuthSvc->>AuthSvc: Validate Input<br/>(name, email unique, password match)
    
    alt Validation Passes
        AuthSvc->>DB: Check Email Exists<br/>(SELECT * FROM users WHERE email = ?)
        DB-->>AuthSvc: Email Available
        
        AuthSvc->>AuthSvc: Hash Password<br/>(Hash::make(password))
        
        AuthSvc->>DB: Create User<br/>(INSERT INTO users)
        DB-->>AuthSvc: User Created (ID)
        
        AuthSvc->>AuthSvc: Generate Access Token<br/>(createToken('Personal Access Token'))
        AuthSvc->>DB: Store Token
        DB-->>AuthSvc: Token Stored
        
        AuthSvc->>Redis: Publish user.registered event<br/>(Queue: events, Priority: NORMAL)
        Redis-->>AuthSvc: Event Published
        
        AuthSvc-->>Client: 201 Created<br/>(user data + Bearer token)
    else Validation Fails
        alt Email Already Exists
            AuthSvc-->>Client: 422 Validation Error<br/>("Email already taken")
        else Other Validation Errors
            AuthSvc-->>Client: 422 Validation Error<br/>(field errors)
        end
    end
```

## 4. Token Validation Flow (Used by Other Services)

```mermaid
sequenceDiagram
    autonumber
    participant Client
    participant Service as Any Service<br/>(Tournament/Match/Team)
    participant AuthSvc as Auth Service
    participant Cache as Token Cache<br/>(Redis)

    Client->>Service: API Request<br/>(Bearer Token)
    
    Note over Service: ValidatePassportToken Middleware
    Service->>Cache: Check Token Cache<br/>(token validation result)
    
    alt Token in Cache
        Cache-->>Service: Cached User Data<br/>(user, roles, permissions)
    else Token Not in Cache
        Service->>AuthSvc: GET /api/auth/me<br/>(Headers: Bearer Token)
        
        AuthSvc->>AuthSvc: Validate Token<br/>(Check oauth_access_tokens)
        
        alt Token Valid
            AuthSvc->>AuthSvc: Load User + Roles + Permissions
            AuthSvc-->>Service: User Data + Roles + Permissions
            
            Service->>Cache: Cache Token Validation<br/>(TTL: 5 minutes)
            Cache-->>Service: Cached
        else Token Invalid
            AuthSvc-->>Service: 401 Unauthorized
            Service-->>Client: 401 Unauthorized
        end
    end
    
    alt User Authenticated
        Service->>Service: Add User Data to Request<br/>(authenticated_user, roles, permissions)
        Service->>Service: Process Request
        Service-->>Client: Response
    else User Not Authenticated
        Service-->>Client: 401 Unauthorized
    end
```

---

## Usage Instructions

1. **Copy the Mermaid code** for the diagram you need
2. **Go to**: https://mermaid.live/
3. **Paste the code** into the editor
4. **Customize** as needed
5. **Export** as PNG or SVG

## Notes

- All diagrams use `autonumber` for step numbering
- Replace service names with your actual service names if different
- Adjust the flow based on your specific implementation
- Add error handling flows as needed
