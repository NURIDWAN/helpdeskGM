import { describe, it, expect, vi, beforeEach } from 'vitest'
import {
    formatRupiah,
    parseRupiah,
    formatPercentage,
    formatDate,
    formatDateTime,
    formatBytes,
} from '@/helpers/format'

describe('format helpers', () => {
    describe('formatRupiah', () => {
        it('should format number to rupiah format', () => {
            expect(formatRupiah(1000000)).toBe('1,000,000')
            expect(formatRupiah(50000)).toBe('50,000')
            expect(formatRupiah(0)).toBe('0')
        })

        it('should handle decimal values', () => {
            expect(formatRupiah(1000.50)).toBe('1,000.50')
            expect(formatRupiah(99.99)).toBe('99.99')
        })

        it('should handle null/undefined', () => {
            expect(formatRupiah(null)).toBe('0')
            expect(formatRupiah(undefined)).toBe('0')
        })
    })

    describe('parseRupiah', () => {
        it('should parse formatted string to number', () => {
            expect(parseRupiah('1,000,000')).toBe(1000000)
            expect(parseRupiah('50,000')).toBe(50000)
        })

        it('should parse decimal strings', () => {
            expect(parseRupiah('1,000.50')).toBe(1000.5)
        })
    })

    describe('formatPercentage', () => {
        it('should format decimal to percentage', () => {
            expect(formatPercentage(0.5)).toBe('50%')
            expect(formatPercentage(1)).toBe('100%')
            expect(formatPercentage(0.255)).toBe('25.50%')
        })

        it('should handle zero', () => {
            expect(formatPercentage(0)).toBe('0%')
        })
    })

    describe('formatDate', () => {
        it('should format date to Indonesian format', () => {
            const date = '2024-01-15'
            const result = formatDate(date)

            expect(result).toContain('15')
            expect(result).toContain('2024')
        })

        it('should handle ISO date string', () => {
            const date = '2024-06-20T10:30:00.000Z'
            const result = formatDate(date)

            expect(result).toContain('2024')
        })
    })

    describe('formatDateTime', () => {
        it('should format datetime with time', () => {
            const date = '2024-01-15T14:30:00'
            const result = formatDateTime(date)

            expect(result).toContain('15')
            expect(result).toContain('2024')
        })
    })

    describe('formatBytes', () => {
        it('should format bytes correctly', () => {
            expect(formatBytes(0)).toBe('0 Bytes')
            expect(formatBytes(1024)).toBe('1 KB')
            expect(formatBytes(1048576)).toBe('1 MB')
            expect(formatBytes(1073741824)).toBe('1 GB')
        })

        it('should handle decimal precision', () => {
            expect(formatBytes(1536, 1)).toBe('1.5 KB')
            expect(formatBytes(1536, 2)).toBe('1.5 KB')
        })

        it('should handle large values', () => {
            expect(formatBytes(1099511627776)).toBe('1 TB')
        })

        it('should handle small values', () => {
            expect(formatBytes(100)).toBe('100 Bytes')
            expect(formatBytes(500)).toBe('500 Bytes')
        })
    })
})
