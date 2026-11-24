"""Predictive engine package.

The engine is a short-lived process: it reads one JSON request from stdin,
calculates, and writes one JSON response to stdout. It never persists domain
state and never talks to PostgreSQL.
"""
