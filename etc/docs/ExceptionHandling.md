# Error Handling Documentation

## Overview

This document outlines the exception handling strategy implemented in the application. It describes the custom exception hierarchy, how exceptions are structured, how contextual data is passed, and how HTTP status codes are determined.

---

## Exception Hierarchy

All exceptions in the application must inherit from one of the two base classes:

### `DomainException`

Used for **business logic errors**. These are not critical to the service itself but indicate problems with the domain flow, validations, or operations.

### `CriticalException`

Used for **infrastructure or system-level failures** that render the service dysfunctional or unstable.  
Examples:
- Database connection failures
- Messaging system outages
- Unavailable external services

---

## Exception Structure

All exceptions share the following structure:

### code
A unique string identifier for each exception.

Used by the frontend to handle specific error scenarios.

The list of codes must follow the centralized definition: see [Error Codes](ErrorCodes.md).

### extraItems Property
All exceptions can provide an optional extraItems associative array that gives context about the error. This improves traceability and allows for enhanced client-side messaging.

## Mapping Exceptions to HTTP Status Codes
To map exceptions to appropriate HTTP responses, the application uses the ApiExceptionsHttpStatusCodeHandler service, which relies on a predefined array that maps each exception class to a corresponding HTTP status code.

For example:

````
$this->exceptionHandler->register(UserEmailAlreadyExists::class, Response::HTTP_CONFLICT);
````

## Best Practices
- Use DomainException for recoverable and business rule errors.
- Use CriticalException for infrastructure or system-level failures.
- Always provide useful extraItems for debugging and UI hints.
- Use descriptive and unique error codes.
- Keep a centralized registry of all defined codes and their meanings.
- Never expose sensitive internal data in exception messages or extraItems.

